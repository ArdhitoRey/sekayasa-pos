<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Business;
use App\Models\ClientLead;
use App\Models\Consultation;
use App\Models\Plan;
use App\Models\SubscriptionPayment;
use App\Models\UatTestCase;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class ClientAccountController extends Controller
{
    private function sekayasaWhatsappNumber(): string
    {
        $raw = (string) config('services.whatsapp.sekayasa_number', '6281234567890');
        $digits = preg_replace('/\D+/', '', $raw) ?: '6281234567890';
        if (str_starts_with($digits, '0')) {
            return '62' . substr($digits, 1);
        }
        return $digits;
    }

    private function buildConsultationWhatsappUrl(ClientLead $lead): string
    {
        $number = $this->sekayasaWhatsappNumber();
        $schedule = $lead->preferred_consultation_at
            ? $lead->preferred_consultation_at->locale('id')->translatedFormat('d M Y') . ' pukul ' . ($lead->consultation_time_slot ?: $lead->preferred_consultation_at->format('H:i') . ' WIB')
            : '-';

        $text = implode("\n", [
            'Halo Tim Tandha by SEKA GROUP,',
            'Saya mengajukan *Konsultasi / Request Penambahan Fitur Tandha*:',
            "• Ref Pengajuan: #KONSUL-{$lead->id}",
            "• Nama Bisnis: {$lead->business_name}",
            "• Status Paket Saat Ini: " . ($lead->subscription_plan_snapshot ?: 'Belum Berlangganan'),
            "• PIC / Owner: {$lead->client_name} ({$lead->email})",
            "• Sektor Usaha: {$lead->business_type}",
            "• Jadwal Pilihan (min. H-3): {$schedule}",
            "• Kebutuhan Fitur:\n{$lead->template_gap_notes}",
            '',
            'Mohon konfirmasi melalui WhatsApp ini. Terima kasih.',
        ]);

        return "https://wa.me/{$number}?text=" . rawurlencode($text);
    }

    private function evaluateConsultationGuard($user): array
    {
        $baseQuery = ClientLead::where(function ($q) use ($user) {
            $q->where('user_id', $user->id);
            if ($user->business_id) {
                $q->orWhere('business_id', $user->business_id);
            }
        });

        $activePending = (clone $baseQuery)
            ->whereIn('whatsapp_status', ['pending_send', 'sent_by_client'])
            ->whereNotIn('status', ['live', 'monthly_service_active'])
            ->latest()
            ->first();

        $latest = (clone $baseQuery)->latest()->first();
        $weeklyUsed = (clone $baseQuery)->where('created_at', '>=', now()->subDays(7))->count();
        $weeklyLimit = 2;

        if ($activePending) {
            return [
                'can_submit' => false,
                'reason' => "Anda masih memiliki pengajuan aktif (#KONSUL-{$activePending->id}) yang sedang menunggu balasan WhatsApp dari tim SEKA GROUP.",
                'cooldown_until' => null,
                'weekly_used' => $weeklyUsed,
                'weekly_limit' => $weeklyLimit,
                'active_whatsapp_url' => $this->buildConsultationWhatsappUrl($activePending),
            ];
        }

        if ($latest && $latest->created_at && $latest->created_at->gt(now()->subHours(24))) {
            $until = $latest->created_at->copy()->addHours(24);
            return [
                'can_submit' => false,
                'reason' => 'Anda baru saja mengirim pengajuan dalam 24 jam terakhir. Pengajuan baru tersedia kembali setelah ' . $until->locale('id')->translatedFormat('d M Y H:i') . ' WIB.',
                'cooldown_until' => $until->toIso8601String(),
                'weekly_used' => $weeklyUsed,
                'weekly_limit' => $weeklyLimit,
                'active_whatsapp_url' => $this->buildConsultationWhatsappUrl($latest),
            ];
        }

        if ($weeklyUsed >= $weeklyLimit) {
            return [
                'can_submit' => false,
                'reason' => "Batas kuota pengajuan mingguan tercapai ({$weeklyUsed}/{$weeklyLimit} kali dalam 7 hari terakhir). Silakan lanjutkan komunikasi lewat WhatsApp SEKA GROUP.",
                'cooldown_until' => null,
                'weekly_used' => $weeklyUsed,
                'weekly_limit' => $weeklyLimit,
                'active_whatsapp_url' => $latest ? $this->buildConsultationWhatsappUrl($latest) : null,
            ];
        }

        return [
            'can_submit' => true,
            'reason' => null,
            'cooldown_until' => null,
            'weekly_used' => $weeklyUsed,
            'weekly_limit' => $weeklyLimit,
            'active_whatsapp_url' => null,
        ];
    }

    /**
     * Client Account Overview: Profile, Available Template Plans, Active Subscription Invoices, and Custom Consultations
     */
    public function overview(Request $request)
    {
        $user = $request->user();
        if (!$user->canManageBusiness()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Hanya Pemilik Bisnis (Owner) yang dapat mengakses portal langganan dan konsultasi bisnis.',
            ], 403);
        }

        $user->load('business.outlets', 'business.currentSubscription.plan', 'outlet');
        $plans = Plan::where('is_active', true)->orderBy('price_monthly')->get();

        $pendingPayment = null;
        $paymentHistory = [];
        $subscriptionSummary = null;
        $usage = [
            'outlets_count' => 0,
            'users_count' => 0,
            'products_count' => 0,
        ];

        if ($user->business_id) {
            $pendingPayment = SubscriptionPayment::with('plan')
                ->where('business_id', $user->business_id)
                ->where('status', 'pending')
                ->where(function ($q) {
                    $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
                })
                ->latest()
                ->first();

            $paymentHistory = SubscriptionPayment::with('plan')
                ->where('business_id', $user->business_id)
                ->latest()
                ->limit(25)
                ->get()
                ->map(function (SubscriptionPayment $p) {
                    if ($p->status === 'verified' && !$p->receipt_number) {
                        $p->receipt_number = $p->resolvedReceiptNumber();
                    }
                    return $p;
                });

            if ($user->business) {
                $subscriptionSummary = $user->business->subscriptionSummary();
                $usage = [
                    'outlets_count' => $user->business->outlets()->count(),
                    'users_count' => $user->business->users()->count(),
                    'products_count' => $user->business->products()->count(),
                ];
            }
        }

        $projects = ClientLead::where(function ($q) use ($user) {
                $q->where('user_id', $user->id);
                if ($user->business_id) {
                    $q->orWhere('business_id', $user->business_id);
                }
            })
            ->with([
                'consultations',
                'discovery',
                'requirements',
                'currentProposal',
                'contracts',
                'uatCases',
                'deploymentTargets',
                'deploymentRuns',
            ])
            ->latest()
            ->get()
            ->map(function (ClientLead $lead) {
                $lead->whatsapp_url = $this->buildConsultationWhatsappUrl($lead);
                return $lead;
            });

        return response()->json([
            'status' => 'success',
            'data' => [
                'user' => $user,
                'plans' => $plans,
                'billing_periods' => array_values(SubscriptionPayment::billingPeriods()),
                'can_install_apk' => $user->business ? $user->business->hasSubscribedAppAccess() : false,
                'subscription_summary' => $subscriptionSummary,
                'usage' => $usage,
                'pending_payment' => $pendingPayment,
                'payment_history' => $paymentHistory,
                'projects' => $projects,
                'consultation_guard' => $this->evaluateConsultationGuard($user),
                'sekayasa_whatsapp_number' => $this->sekayasaWhatsappNumber(),
            ],
        ]);
    }

    /**
     * Create Subscription Invoice for POS Template Plan with selectable billing duration (1/3/6 months, 1/2/3 years).
     */
    public function rentTemplate(Request $request)
    {
        $user = $request->user();
        if (!$user->canManageBusiness()) {
            return response()->json(['status' => 'error', 'message' => 'Hanya Pemilik Bisnis (Owner) yang dapat mengelola langganan.'], 403);
        }
        $validated = $request->validate([
            'plan_id' => 'required|exists:plans,id',
            'duration_months' => 'nullable|integer|in:1,3,6,12,24,36',
        ]);

        if (!$user->business) {
            return response()->json([
                'status' => 'error',
                'message' => 'Akun tidak terhubung dengan profil bisnis.',
            ], 400);
        }

        $plan = Plan::where('is_active', true)->findOrFail($validated['plan_id']);
        $business = $user->business()->with('currentSubscription.plan')->firstOrFail();

        $activeSub = $business->currentSubscription;
        if (
            $activeSub
            && $activeSub->isActive()
            && $activeSub->status === 'active'
            && $activeSub->plan
            && (float) $plan->price_monthly < (float) $activeSub->plan->price_monthly
        ) {
            return response()->json([
                'status' => 'error',
                'message' => "Paket {$activeSub->plan->name} Anda masih aktif. Penurunan paket (downgrade) hanya dapat dilakukan setelah masa aktif berakhir.",
            ], 422);
        }

        $periods = SubscriptionPayment::billingPeriods();
        $months = (int) ($validated['duration_months'] ?? 1);
        $period = $periods[$months] ?? $periods[1];

        $payment = DB::transaction(function () use ($business, $plan, $period) {
            Business::whereKey($business->id)->lockForUpdate()->firstOrFail();

            SubscriptionPayment::where('business_id', $business->id)
                ->where('status', 'pending')
                ->update(['status' => 'rejected', 'notes' => 'Digantikan tagihan langganan baru']);

            $amount = (int) round((float) $plan->price_monthly) * $period['months'];
            $invoiceNumber = 'SUB-' . date('Ymd') . '-' . strtoupper(Str::random(5));

            $payment = SubscriptionPayment::create([
                'business_id' => $business->id,
                'plan_id' => $plan->id,
                'plan_name_snapshot' => $plan->name,
                'duration_months' => $period['months'],
                'duration_days' => $period['days'],
                'invoice_number' => $invoiceNumber,
                'base_amount' => $amount,
                'unique_code' => 0,
                'amount' => $amount,
                'payment_method' => 'midtrans_pending',
                'status' => 'pending',
                'notes' => "Tagihan Paket {$plan->name} ({$period['label']} — {$period['days']} hari)",
                'expires_at' => now()->addDays(1),
            ]);

            ActivityLog::record(
                $business->id,
                auth()->id(),
                'subscription_invoice_created',
                'SubscriptionPayment',
                $payment->id,
                ['invoice_number' => $invoiceNumber, 'plan' => $plan->name, 'duration_months' => $period['months'], 'amount' => $amount],
                request()->ip()
            );

            return $payment;
        });

        // Request Snap token automatically when Midtrans Server Key is configured
        $snapToken = null;
        $snapRedirectUrl = null;
        $midtransKey = (string) config('services.midtrans.server_key', '');
        if ($midtransKey !== '' && !app()->runningUnitTests()) {
            try {
                $snapBase = config('services.midtrans.is_production')
                    ? 'https://app.midtrans.com/snap/v1/transactions'
                    : 'https://app.sandbox.midtrans.com/snap/v1/transactions';
                $snapRes = Http::withBasicAuth($midtransKey, '')
                    ->acceptJson()
                    ->timeout(6)
                    ->post($snapBase, [
                        'transaction_details' => [
                            'order_id' => $payment->invoice_number,
                            'gross_amount' => (int) round((float) $payment->amount),
                        ],
                        'item_details' => [[
                            'id' => 'PLAN-' . $plan->id,
                            'price' => (int) round((float) $payment->amount),
                            'quantity' => 1,
                            'name' => mb_substr("Tandha {$plan->name} ({$period['label']})", 0, 50),
                        ]],
                        'customer_details' => [
                            'first_name' => $user->name,
                            'email' => $user->email,
                            'phone' => $user->phone ?: ($business->phone ?: ''),
                        ],
                    ]);
                if ($snapRes->successful()) {
                    $snapToken = $snapRes->json('token');
                    $snapRedirectUrl = $snapRes->json('redirect_url');
                    if ($snapRedirectUrl || $snapToken) {
                        $payment->update([
                            'qris_payload' => $snapRedirectUrl ?: $snapToken,
                        ]);
                    }
                }
            } catch (\Throwable $ignored) {
            }
        }

        return response()->json([
            'status' => 'success',
            'message' => "Tagihan langganan {$payment->invoice_number} ({$period['label']}) sebesar Rp " . number_format((float) $payment->amount, 0, ',', '.') . " berhasil dibuat.",
            'data' => [
                'payment' => $payment->load('plan'),
                'snap_token' => $snapToken,
                'snap_redirect_url' => $snapRedirectUrl,
            ],
        ], 201);
    }

    /**
     * Poll Subscription Payment Status
     */
    public function checkPaymentStatus(Request $request, $paymentId)
    {
        $user = $request->user();
        if (!$user->canManageBusiness()) {
            return response()->json(['status' => 'error', 'message' => 'Hanya Pemilik Bisnis (Owner) yang dapat melihat status tagihan langganan.'], 403);
        }
        $payment = SubscriptionPayment::with('plan', 'business.currentSubscription.plan', 'subscription')
            ->where('business_id', $user->business_id)
            ->findOrFail($paymentId);

        $isExpired = $payment->status === 'pending' && $payment->expires_at && $payment->expires_at->isPast();

        return response()->json([
            'status' => 'success',
            'data' => [
                'id' => $payment->id,
                'invoice_number' => $payment->invoice_number,
                'receipt_number' => $payment->resolvedReceiptNumber(),
                'payment_status' => $isExpired ? 'expired' : $payment->status,
                'amount' => (float) $payment->amount,
                'verified_at' => $payment->verified_at,
                'service_type' => $payment->business->service_type,
                'receipt' => $payment->status === 'verified' ? $payment->toReceiptPayload() : null,
            ],
        ]);
    }

    /**
     * Official Printable Payment Receipt (Strictly available only after payment is verified/settled)
     */
    public function paymentReceipt(Request $request, $paymentId)
    {
        $user = $request->user();
        if (!$user->canManageBusiness()) {
            return response()->json(['status' => 'error', 'message' => 'Hanya Pemilik Bisnis (Owner) yang dapat mengakses nota pembayaran langganan.'], 403);
        }

        $payment = SubscriptionPayment::with(['business', 'plan', 'subscription'])
            ->where('business_id', $user->business_id)
            ->findOrFail($paymentId);

        if ($payment->status !== 'verified') {
            return response()->json([
                'status' => 'error',
                'message' => 'Nota lunas hanya tersedia untuk pembayaran yang sudah disetujui/terverifikasi.',
            ], 403);
        }

        if (!$payment->receipt_number) {
            $payment->update(['receipt_number' => $payment->resolvedReceiptNumber()]);
        }

        return response()->json([
            'status' => 'success',
            'data' => $payment->toReceiptPayload(),
        ]);
    }

    /**
     * Midtrans Payment Gateway Callback Webhook (Signature-verified & idempotent lock-protected)
     */
    public function handleMidtransCallback(Request $request)
    {
        $serverKey = (string) config('services.midtrans.server_key', '');
        if ($serverKey === '') {
            return response()->json([
                'status' => 'error',
                'message' => 'Midtrans Server Key belum dikonfigurasi.',
            ], 503);
        }

        $orderId = (string) $request->input('order_id', '');
        $statusCode = (string) $request->input('status_code', '');
        $grossAmount = (string) $request->input('gross_amount', '');
        $signatureKey = (string) $request->input('signature_key', '');

        $expectedSignature = hash('sha512', $orderId . $statusCode . $grossAmount . $serverKey);
        if (!hash_equals($expectedSignature, $signatureKey)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Signature pembayaran tidak valid.',
            ], 403);
        }

        $payment = SubscriptionPayment::where('invoice_number', $orderId)->firstOrFail();

        if ((int) round((float) $grossAmount) !== (int) round((float) $payment->amount)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Nominal pembayaran tidak sesuai tagihan.',
            ], 422);
        }

        $txStatus = (string) $request->input('transaction_status', '');
        $fraudStatus = $request->input('fraud_status');

        if (in_array($txStatus, ['capture', 'settlement'], true) && ($fraudStatus === null || $fraudStatus === 'accept')) {
            if ($payment->status === 'pending' && $payment->expires_at && $payment->expires_at->isPast()) {
                $payment->update([
                    'status' => 'rejected',
                    'webhook_payload' => $request->all(),
                    'notes' => ($payment->notes ? $payment->notes . ' | ' : '') . 'Tagihan sudah kedaluwarsa saat callback diterima',
                ]);
                return response()->json([
                    'status' => 'error',
                    'message' => 'Tagihan langganan sudah kedaluwarsa dan tidak dapat diaktifkan.',
                ], 422);
            }

            try {
                $method = 'midtrans_' . strtolower((string) $request->input('payment_type', 'gateway'));
                $res = $payment->approveAndActivate(
                    null,
                    $method,
                    'Pembayaran lunas otomatis via Midtrans Payment Gateway',
                    $request->all()
                );

                if (empty($res['already_verified'])) {
                    ActivityLog::record(
                        $payment->business_id,
                        null,
                        'midtrans_payment_verified',
                        'SubscriptionPayment',
                        $payment->id,
                        [
                            'invoice_number' => $payment->invoice_number,
                            'receipt_number' => $res['payment']->receipt_number,
                            'amount' => (float) $payment->amount,
                        ],
                        $request->ip()
                    );
                }

                return response()->json([
                    'status' => 'success',
                    'message' => 'Pembayaran diterima dan nota lunas diterbitkan.',
                    'data' => $res['payment']->toReceiptPayload(),
                ]);
            } catch (\InvalidArgumentException $e) {
                return response()->json(['status' => 'error', 'message' => $e->getMessage()], 422);
            }
        }

        if (in_array($txStatus, ['deny', 'cancel', 'expire'], true) && $payment->status === 'pending') {
            $payment->update([
                'status' => 'rejected',
                'webhook_payload' => $request->all(),
                'notes' => ($payment->notes ? $payment->notes . ' | ' : '') . "Ditutup oleh gateway ({$txStatus})",
            ]);
        }

        return response()->json(['status' => 'success', 'message' => 'Notifikasi status pembayaran diterima.']);
    }

    /**
     * Request Custom Feature Addition / Consultation (Allowed both BEFORE & AFTER subscribing; enforces H-3, hours, & anti-spam)
     */
    public function requestConsultation(Request $request)
    {
        $user = $request->user();
        if (!$user->canManageBusiness()) {
            return response()->json(['status' => 'error', 'message' => 'Hanya Pemilik Bisnis (Owner) yang dapat mengajukan konsultasi fitur.'], 403);
        }

        $validated = $request->validate([
            'business_type' => 'required|string|max:100',
            'business_type_other' => 'nullable|string|max:100',
            'scale' => 'required|string|max:100',
            'template_gap_notes' => 'required|string|min:15|max:1500',
            'preferred_date' => 'nullable|date_format:Y-m-d',
            'preferred_time' => 'nullable|date_format:H:i',
            'preferred_consultation_at' => 'nullable|date',
            'phone' => 'nullable|string|max:25',
        ]);

        $resolvedSector = trim($validated['business_type']);
        if (strcasecmp($resolvedSector, 'Lainnya') === 0) {
            $other = trim((string) ($validated['business_type_other'] ?? ''));
            if (mb_strlen($other) < 3) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Mohon tulis bidang usaha Anda pada kolom "Bidang Usaha Lainnya".',
                    'errors' => ['business_type_other' => ['Bidang usaha lainnya wajib diisi minimal 3 karakter.']],
                ], 422);
            }
            $resolvedSector = 'Lainnya: ' . $other;
        }

        // Anti-spam check before saving
        $guard = $this->evaluateConsultationGuard($user);
        if (!$guard['can_submit']) {
            return response()->json([
                'status' => 'error',
                'code' => 'CONSULTATION_RATE_LIMITED',
                'message' => $guard['reason'],
                'data' => $guard,
            ], 429);
        }

        // Duplicate note anti-spam check
        $notesClean = trim($validated['template_gap_notes']);
        $duplicateExists = ClientLead::where(function ($q) use ($user) {
                $q->where('user_id', $user->id);
                if ($user->business_id) {
                    $q->orWhere('business_id', $user->business_id);
                }
            })
            ->where('created_at', '>=', now()->subDays(7))
            ->where('template_gap_notes', $notesClean)
            ->exists();
        if ($duplicateExists) {
            return response()->json([
                'status' => 'error',
                'message' => 'Permintaan fitur yang identik sudah pernah dikirim dalam 7 hari terakhir.',
            ], 422);
        }

        // Parse and enforce minimum H-3 (date >= today + 3 days) and working hours (09:00–17:00)
        if (!empty($validated['preferred_date']) && !empty($validated['preferred_time'])) {
            $scheduledAt = Carbon::createFromFormat('Y-m-d H:i', $validated['preferred_date'] . ' ' . $validated['preferred_time']);
        } elseif (!empty($validated['preferred_consultation_at'])) {
            $scheduledAt = Carbon::parse($validated['preferred_consultation_at']);
        } else {
            return response()->json([
                'status' => 'error',
                'message' => 'Pilih tanggal dan jam konsultasi yang diinginkan.',
            ], 422);
        }

        $minDate = now()->startOfDay()->addDays(3);
        $maxDate = now()->startOfDay()->addDays(90);
        if ($scheduledAt->copy()->startOfDay()->lt($minDate)) {
            return response()->json([
                'status' => 'error',
                'code' => 'MIN_H3_REQUIRED',
                'message' => 'Jadwal konsultasi minimal H-3 (paling cepat tanggal ' . $minDate->locale('id')->translatedFormat('d M Y') . ') agar pengajuan sempat ditinjau dan dijawab oleh tim SEKA GROUP.',
                'errors' => ['preferred_date' => ['Tanggal konsultasi minimal 3 hari dari hari ini (H-3).']],
            ], 422);
        }
        if ($scheduledAt->copy()->startOfDay()->gt($maxDate)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Jadwal konsultasi maksimal 90 hari ke depan.',
            ], 422);
        }

        $hour = (int) $scheduledAt->format('G');
        if ($hour < 9 || $hour > 17) {
            return response()->json([
                'status' => 'error',
                'code' => 'INVALID_CONSULTATION_HOUR',
                'message' => 'Jam konsultasi hanya tersedia pada jam layanan 09:00 – 17:00 WIB.',
                'errors' => ['preferred_time' => ['Pilih jam konsultasi antara pukul 09:00 hingga 17:00 WIB.']],
            ], 422);
        }

        $timeSlotLabel = $scheduledAt->format('H:i') . ' WIB';
        $business = $user->business;
        $subSummary = $business ? $business->subscriptionSummary() : null;
        $planSnapshot = ($subSummary && !empty($subSummary['is_active']))
            ? ($subSummary['plan_name'] . ' (Aktif s.d. ' . ($subSummary['expires_at_formatted'] ?? '-') . ')')
            : 'Belum Berlangganan (Pra-Langganan)';

        return DB::transaction(function () use ($user, $business, $validated, $resolvedSector, $scheduledAt, $timeSlotLabel, $planSnapshot, $notesClean) {
            if (!empty($validated['phone'])) {
                $user->update(['phone' => $validated['phone']]);
                if ($business) {
                    $business->update(['phone' => $validated['phone']]);
                }
            }

            $lead = ClientLead::create([
                'user_id' => $user->id,
                'business_id' => $business ? $business->id : null,
                'client_name' => $user->name,
                'business_name' => $business ? $business->name : $user->name,
                'email' => $user->email,
                'phone' => $validated['phone'] ?? $user->phone,
                'business_type' => $resolvedSector,
                'scale' => $validated['scale'],
                'subscription_plan_snapshot' => $planSnapshot,
                'source' => ($business && $business->service_type === 'template_saas') ? 'Portal Klien (Pelanggan Aktif)' : 'Portal Klien (Pra-Langganan)',
                'status' => 'consultation_scheduled',
                'notes' => 'Request Penambahan Fitur: ' . $notesClean,
                'template_gap_notes' => $notesClean,
                'preferred_consultation_at' => $scheduledAt,
                'consultation_time_slot' => $timeSlotLabel,
                'whatsapp_status' => 'sent_by_client',
            ]);

            Consultation::create([
                'lead_id' => $lead->id,
                'scheduled_at' => $scheduledAt,
                'pic_name' => 'Business Analyst SEKA GROUP',
                'consultation_notes' => $notesClean,
                'status' => 'scheduled',
            ]);

            if ($business && $business->service_type === 'none') {
                $business->update(['service_type' => 'custom_pos']);
            }

            ActivityLog::record(
                $business?->id,
                $user->id,
                'custom_consultation_submitted',
                'ClientLead',
                $lead->id,
                [
                    'business_type' => $resolvedSector,
                    'scale' => $validated['scale'],
                    'scheduled_at' => $scheduledAt->toDateTimeString(),
                    'time_slot' => $timeSlotLabel,
                ],
                request()->ip()
            );

            $lead->load('consultations');
            $lead->whatsapp_url = $this->buildConsultationWhatsappUrl($lead);

            return response()->json([
                'status' => 'success',
                'message' => 'Jadwal konsultasi & request fitur berhasil dicatat. Silakan lanjutkan konfirmasi langsung di WhatsApp SEKA GROUP.',
                'data' => $lead,
                'whatsapp_url' => $lead->whatsapp_url,
            ], 201);
        });
    }

    /**
     * Client submits UAT result strictly on their own custom project (OPS-026 / OPS-028)
     */
    public function submitOwnUat(Request $request, $testCaseId)
    {
        $user = $request->user();
        if (!$user->canManageBusiness()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Hanya Pemilik Bisnis (Owner) yang dapat mengirimkan hasil pengujian UAT proyek.',
            ], 403);
        }
        $validated = $request->validate([
            'status' => 'required|in:pass,fail,blocked',
            'client_feedback' => 'nullable|string',
        ]);

        $testCase = UatTestCase::with('lead')->findOrFail($testCaseId);

        $isOwner = $testCase->lead && (
            $testCase->lead->user_id === $user->id ||
            ($user->business_id && $testCase->lead->business_id === $user->business_id)
        );

        if (!$isOwner) {
            return response()->json([
                'status' => 'error',
                'message' => 'Akses ditolak: Anda tidak memiliki otorisasi atas proyek ini.',
            ], 403);
        }

        $testCase->update([
            'status' => $validated['status'],
            'client_feedback' => $validated['client_feedback'] ?? null,
            'tested_at' => now(),
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Hasil pengujian UAT Anda telah disimpan.',
            'data' => $testCase,
        ]);
    }
}
