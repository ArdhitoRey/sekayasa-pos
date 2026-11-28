<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Business;
use App\Models\ClientLead;
use App\Models\Outlet;
use App\Models\Plan;
use App\Models\Product;
use App\Models\Subscription;
use App\Models\SubscriptionPayment;
use App\Models\SupportTicket;
use App\Models\Transaction;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class AdminController extends Controller
{
    private function ensureSuperAdmin(Request $request): void
    {
        if (!$request->user() || !$request->user()->isSuperAdmin()) {
            abort(403, 'Akses khusus Super Admin.');
        }
    }

    /**
     * Parse the tail of storage/logs/laravel.log safely so Super Admin can trace runtime errors.
     */
    private function readRecentSystemErrors(int $limit = 25): array
    {
        $logPath = storage_path('logs/laravel.log');
        if (!is_file($logPath) || !is_readable($logPath)) {
            return [];
        }

        $size = filesize($logPath);
        if (!$size) {
            return [];
        }

        $readBytes = min($size, 65536); // Read only last 64KB
        $handle = fopen($logPath, 'rb');
        if (!$handle) {
            return [];
        }

        if ($size > $readBytes) {
            fseek($handle, -$readBytes, SEEK_END);
        }
        $chunk = fread($handle, $readBytes) ?: '';
        fclose($handle);

        $lines = preg_split("/\r\n|\n|\r/", $chunk);
        $entries = [];
        $current = null;

        foreach ($lines as $line) {
            if (preg_match('/^\[(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})\]\s+[\w.-]+\.(ERROR|CRITICAL|ALERT|EMERGENCY|WARNING):\s+(.*)$/', $line, $m)) {
                if ($current !== null) {
                    $entries[] = $current;
                }
                $cleanMsg = preg_replace('/(Bearer\s+[A-Za-z0-9\-_.=|]+|password\s*[:=]\s*\S+|secret[_a-z]*\s*[:=]\s*\S+)/i', '[REDACTED]', trim($m[3]));
                $current = [
                    'timestamp' => $m[1],
                    'level' => $m[2],
                    'message' => mb_substr($cleanMsg, 0, 320),
                    'context_line' => '',
                ];
            } elseif ($current !== null && $current['context_line'] === '' && trim($line) !== '' && !str_starts_with(trim($line), '[stacktrace]')) {
                $current['context_line'] = mb_substr(trim($line), 0, 220);
            }
        }

        if ($current !== null) {
            $entries[] = $current;
        }

        return array_slice(array_reverse($entries), 0, $limit);
    }

    /**
     * Platform-wide Monitoring Overview for Super Admin (All Clients, Subscriptions, Consultations, Activity & Error Logs)
     */
    public function overview(Request $request)
    {
        $this->ensureSuperAdmin($request);

        $today = Carbon::today();

        // 1. All client businesses with aggregated operational counts & last transaction timestamp
        $lastTxPerBusiness = Transaction::select('business_id', DB::raw('MAX(created_at) as last_tx_at'))
            ->groupBy('business_id')
            ->pluck('last_tx_at', 'business_id');

        $lastLogPerBusiness = ActivityLog::whereNotNull('business_id')
            ->select('business_id', DB::raw('MAX(created_at) as last_log_at'))
            ->groupBy('business_id')
            ->pluck('last_log_at', 'business_id');

        $anomalyActions = ['login_failed', 'pos_checkout_failed', 'pos_void_transaction', 'system_exception'];
        $anomaliesPerBusiness = ActivityLog::whereNotNull('business_id')
            ->whereIn('action', $anomalyActions)
            ->select('business_id', DB::raw('COUNT(*) as total'))
            ->groupBy('business_id')
            ->pluck('total', 'business_id');

        $businesses = Business::with(['currentSubscription.plan'])
            ->withCount(['outlets', 'users', 'products', 'transactions'])
            ->withSum(['transactions as paid_revenue_sum' => function ($q) {
                $q->where('payment_status', 'paid');
            }], 'total_amount')
            ->latest()
            ->get()
            ->map(function ($b) use ($lastTxPerBusiness, $lastLogPerBusiness, $anomaliesPerBusiness) {
                $summary = $b->subscriptionSummary();
                $b->last_transaction_at = $lastTxPerBusiness[$b->id] ?? null;
                $b->last_activity_at = $lastLogPerBusiness[$b->id] ?? $b->last_transaction_at ?? $b->updated_at;
                $b->anomaly_count = (int) ($anomaliesPerBusiness[$b->id] ?? 0);
                $b->subscription_summary = $summary;
                return $b;
            });

        // 2. Payments, Plans & Consultations
        $plans = Plan::where('is_active', true)->orderBy('price_monthly')->get();

        $payments = SubscriptionPayment::with(['business', 'plan'])
            ->latest()
            ->limit(40)
            ->get();

        $consultations = ClientLead::with(['consultations', 'user', 'business'])
            ->latest()
            ->limit(80)
            ->get()
            ->map(function (ClientLead $c) {
                $digits = preg_replace('/\D+/', '', (string) ($c->phone ?? $c->user?->phone ?? ''));
                if (str_starts_with($digits, '0')) {
                    $digits = '62' . substr($digits, 1);
                }
                $sched = $c->preferred_consultation_at
                    ? $c->preferred_consultation_at->locale('id')->translatedFormat('d M Y') . ' pukul ' . ($c->consultation_time_slot ?: $c->preferred_consultation_at->format('H:i') . ' WIB')
                    : '-';
                $replyMsg = "Halo {$c->client_name} ({$c->business_name}), kami dari tim *Tandha by SEKA GROUP* telah menerima Request Penambahan Fitur (#KONSUL-{$c->id}) untuk jadwal {$sched}. Berikut tindak lanjut dari tim kami:";
                $c->whatsapp_client_url = $digits !== '' ? "https://wa.me/{$digits}?text=" . rawurlencode($replyMsg) : null;
                $c->days_until_consultation = $c->preferred_consultation_at
                    ? now()->startOfDay()->diffInDays($c->preferred_consultation_at->copy()->startOfDay(), false)
                    : null;
                return $c;
            });

        // 3. Recent cross-tenant transactions (including voided/held for monitoring)
        $recentTransactions = Transaction::with(['business:id,name', 'outlet:id,name', 'cashier:id,name'])
            ->latest()
            ->limit(40)
            ->get();

        // 4. Activity & Security/Error Audit Logs across all clients
        $activityLogs = ActivityLog::with(['business:id,name', 'user:id,name,email,role'])
            ->latest()
            ->limit(100)
            ->get();

        $errorLogs = $this->readRecentSystemErrors(35);
        $failedJobsCount = Schema::hasTable('failed_jobs') ? DB::table('failed_jobs')->count() : 0;
        $supportTickets = SupportTicket::with('lead:id,business_name,client_name')->latest()->limit(20)->get();

        // 5. Platform & Subscription health metrics
        $todayTxQuery = Transaction::whereDate('created_at', $today)->where('payment_status', 'paid');
        $activeSubsCount = $businesses->filter(fn ($b) => in_array($b->subscription_summary['lifecycle_status'] ?? '', ['active', 'expiring_soon'], true))->count();
        $trialSubsCount = $businesses->filter(fn ($b) => ($b->subscription_summary['lifecycle_status'] ?? '') === 'trial')->count();
        $expiringSoonCount = $businesses->filter(fn ($b) => ($b->subscription_summary['lifecycle_status'] ?? '') === 'expiring_soon')->count();
        $expiredSubsCount = $businesses->filter(fn ($b) => ($b->subscription_summary['lifecycle_status'] ?? '') === 'expired')->count();
        $unactivatedSubsCount = $businesses->filter(fn ($b) => in_array($b->subscription_summary['lifecycle_status'] ?? '', ['none', 'expired'], true))->count();
        $mrrEstimate = $businesses->sum(function ($b) {
            $status = $b->subscription_summary['lifecycle_status'] ?? '';
            return in_array($status, ['active', 'expiring_soon'], true)
                ? (float) ($b->subscription_summary['price_monthly'] ?? 0)
                : 0;
        });

        $metrics = [
            'total_businesses' => $businesses->count(),
            'active_businesses' => $businesses->where('is_active', true)->count(),
            'total_outlets' => Outlet::count(),
            'total_users' => User::where('role', '!=', 'superadmin')->count(),
            'today_transactions' => (int) $todayTxQuery->count(),
            'today_revenue' => (float) $todayTxQuery->sum('total_amount'),
            'pending_payments' => SubscriptionPayment::where('status', 'pending')->count(),
            'active_subscriptions' => $activeSubsCount,
            'trial_subscriptions' => $trialSubsCount,
            'expiring_soon_count' => $expiringSoonCount,
            'expired_subscriptions_count' => $expiredSubsCount,
            'unactivated_subscriptions_count' => $unactivatedSubsCount,
            'mrr_estimate' => (float) $mrrEstimate,
            'error_log_count' => count($errorLogs) + $failedJobsCount + $activityLogs->whereIn('action', $anomalyActions)->count(),
            'failed_jobs_count' => $failedJobsCount,
            'consultation_pending_wa_count' => $consultations->whereIn('whatsapp_status', ['pending_send', 'sent_by_client'])->count(),
        ];

        return response()->json([
            'status' => 'success',
            'data' => [
                'metrics' => $metrics,
                'plans' => $plans,
                'businesses' => $businesses,
                'subscription_payments' => $payments,
                'consultations' => $consultations,
                'recent_transactions' => $recentTransactions,
                'activity_logs' => $activityLogs,
                'error_logs' => $errorLogs,
                'support_tickets' => $supportTickets,
            ],
        ]);
    }

    /**
     * Deep-dive Client Inspection for Super Admin (Subscription, Staff, Outlets, Transactions & Audit Logs)
     */
    public function businessDetail(Request $request, $businessId)
    {
        $this->ensureSuperAdmin($request);

        $business = Business::with(['currentSubscription.plan', 'outlets'])
            ->withCount(['outlets', 'users', 'products', 'transactions'])
            ->withSum(['transactions as paid_revenue_sum' => function ($q) {
                $q->where('payment_status', 'paid');
            }], 'total_amount')
            ->findOrFail($businessId);

        $business->subscription_summary = $business->subscriptionSummary();

        $users = User::where('business_id', $business->id)
            ->with('outlet:id,name')
            ->orderByDesc('role')
            ->get(['id', 'business_id', 'outlet_id', 'name', 'email', 'phone', 'job_title', 'role', 'is_active', 'created_at']);

        $payments = SubscriptionPayment::with('plan')
            ->where('business_id', $business->id)
            ->latest()
            ->limit(15)
            ->get();

        $transactions = Transaction::with(['outlet:id,name', 'cashier:id,name'])
            ->where('business_id', $business->id)
            ->latest()
            ->limit(20)
            ->get();

        $activityLogs = ActivityLog::with('user:id,name,email,role')
            ->where('business_id', $business->id)
            ->latest()
            ->limit(35)
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => [
                'business' => $business,
                'subscription_summary' => $business->subscription_summary,
                'users' => $users,
                'payments' => $payments,
                'transactions' => $transactions,
                'activity_logs' => $activityLogs,
            ],
        ]);
    }

    /**
     * Shared subscription activation/extension keeping unused future days intact.
     */
    private function applySubscriptionPeriod(Business $business, Plan $plan, int $days, string $status = 'active'): Subscription
    {
        return $business->applySubscriptionPeriod($plan, $days, $status);
    }

    /**
     * Manually Extend or Assign Client Subscription Duration from Super Admin Console
     */
    public function extendSubscription(Request $request, $businessId)
    {
        $this->ensureSuperAdmin($request);

        $validated = $request->validate([
            'days' => 'required|integer|min:1|max:1095',
            'plan_id' => 'nullable|exists:plans,id',
            'status' => 'nullable|in:active,trial',
        ]);

        $business = Business::with('currentSubscription.plan')->findOrFail($businessId);
        $plan = !empty($validated['plan_id'])
            ? Plan::findOrFail($validated['plan_id'])
            : ($business->currentSubscription?->plan ?? Plan::where('is_active', true)->orderBy('price_monthly')->firstOrFail());

        $status = $validated['status'] ?? 'active';
        $subscription = DB::transaction(fn () => $this->applySubscriptionPeriod($business, $plan, (int) $validated['days'], $status));

        ActivityLog::record(
            $business->id,
            $request->user()->id,
            'admin_extend_subscription',
            'Subscription',
            $subscription->id,
            [
                'plan' => $plan->name,
                'days_added' => (int) $validated['days'],
                'status' => $status,
                'expires_at' => $subscription->expires_at?->toDateTimeString(),
            ],
            $request->ip()
        );

        return response()->json([
            'status' => 'success',
            'message' => "Langganan {$business->name} ({$plan->name}) aktif hingga " . $subscription->expires_at->locale('id')->translatedFormat('d M Y') . '.',
            'data' => [
                'subscription' => $subscription,
                'subscription_summary' => $subscription->toMonitoringSummary(),
            ],
        ]);
    }

    /**
     * Activate or Suspend a Client Business Tenant
     */
    public function toggleBusinessStatus(Request $request, $businessId)
    {
        $this->ensureSuperAdmin($request);

        $business = Business::findOrFail($businessId);
        $business->update(['is_active' => !$business->is_active]);

        ActivityLog::record(
            $business->id,
            $request->user()->id,
            $business->is_active ? 'admin_activate_business' : 'admin_suspend_business',
            'Business',
            $business->id,
            ['business_name' => $business->name, 'is_active' => $business->is_active],
            $request->ip()
        );

        return response()->json([
            'status' => 'success',
            'message' => $business->is_active
                ? "Akses login untuk akun bisnis {$business->name} diizinkan kembali."
                : "Akses login untuk akun bisnis {$business->name} dibekukan sementara.",
            'data' => $business,
        ]);
    }

    /**
     * Permanently Delete a Client Business Tenant and all its users, outlets, products, transactions & leads.
     */
    public function destroyBusiness(Request $request, $businessId)
    {
        $this->ensureSuperAdmin($request);

        $business = Business::withCount(['outlets', 'users', 'transactions'])->findOrFail($businessId);
        $bizSnapshot = [
            'deleted_business_id' => $business->id,
            'business_name' => $business->name,
            'owner_email' => $business->email,
            'outlets_count' => $business->outlets_count,
            'users_count' => $business->users_count,
            'transactions_count' => $business->transactions_count,
        ];

        $productImagePaths = Product::where('business_id', $business->id)
            ->pluck('image_path')
            ->filter(fn ($p) => !empty($p) && !str_starts_with($p, '/assets/') && !str_starts_with($p, 'http'))
            ->values()
            ->all();

        DB::transaction(function () use ($business) {
            $userIds = User::where('business_id', $business->id)
                ->where('role', '!=', 'superadmin')
                ->pluck('id');

            if ($userIds->isNotEmpty()) {
                DB::table('personal_access_tokens')
                    ->where('tokenable_type', User::class)
                    ->whereIn('tokenable_id', $userIds)
                    ->delete();
            }

            ClientLead::where(function ($q) use ($business, $userIds) {
                $q->where('business_id', $business->id);
                if ($userIds->isNotEmpty()) {
                    $q->orWhereIn('user_id', $userIds);
                }
            })->delete();

            if ($userIds->isNotEmpty()) {
                User::whereIn('id', $userIds)->delete();
            }
            $business->delete();
        });

        foreach ($productImagePaths as $imgPath) {
            Storage::disk('public')->delete($imgPath);
        }

        ActivityLog::record(
            null,
            $request->user()->id,
            'admin_delete_business',
            'Business',
            (int) $businessId,
            $bizSnapshot,
            $request->ip()
        );

        return response()->json([
            'status' => 'success',
            'message' => "Data bisnis {$bizSnapshot['business_name']} beserta seluruh akun, outlet, dan transaksinya berhasil dihapus permanen.",
        ]);
    }

    /**
     * Verify Pending Subscription Payment (while Midtrans integration is pending)
     */
    public function verifySubscriptionPayment(Request $request, $paymentId)
    {
        $this->ensureSuperAdmin($request);

        $payment = SubscriptionPayment::with('business', 'plan')->findOrFail($paymentId);

        try {
            $res = $payment->approveAndActivate(
                $request->user()->id,
                'verified_manual_admin',
                'Diverifikasi Super Admin (Nota Lunas diterbitkan)'
            );
        } catch (\InvalidArgumentException $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 422);
        }

        if (!empty($res['already_verified'])) {
            return response()->json([
                'status' => 'success',
                'message' => 'Pembayaran ini sudah diverifikasi sebelumnya.',
                'data' => $res['payment'],
            ]);
        }

        $verifiedPayment = $res['payment'];
        $subscription = $res['subscription'];

        ActivityLog::record(
            $verifiedPayment->business->id,
            $request->user()->id,
            'admin_verify_payment',
            'SubscriptionPayment',
            $verifiedPayment->id,
            [
                'invoice_number' => $verifiedPayment->invoice_number,
                'receipt_number' => $verifiedPayment->receipt_number,
                'plan' => $verifiedPayment->plan?->name,
                'expires_at' => $subscription->expires_at?->toDateTimeString(),
            ],
            $request->ip()
        );

        return response()->json([
            'status' => 'success',
            'message' => "Pembayaran {$verifiedPayment->invoice_number} (Nota {$verifiedPayment->receipt_number}) berhasil diverifikasi dan layanan POS klien aktif s.d. " . $subscription->expires_at->locale('id')->translatedFormat('d M Y') . '.',
            'data' => [
                'payment' => $verifiedPayment,
                'subscription' => $subscription,
                'subscription_summary' => $subscription->toMonitoringSummary(),
            ],
        ]);
    }

    /**
     * Super Admin marks WhatsApp reminder / SekaYasa dispatch status on a feature consultation lead.
     */
    public function updateConsultationWhatsappStatus(Request $request, $id)
    {
        $this->ensureSuperAdmin($request);

        $validated = $request->validate([
            'whatsapp_status' => 'required|in:pending_send,sent_from_sekayasa,completed',
            'reminder_notes' => 'nullable|string|max:500',
        ]);

        $lead = ClientLead::findOrFail($id);
        $status = $validated['whatsapp_status'];

        $updateData = [
            'whatsapp_status' => $status,
            'reminder_notes' => $validated['reminder_notes'] ?? $lead->reminder_notes,
        ];

        if (in_array($status, ['sent_from_sekayasa', 'completed'], true)) {
            $updateData['whatsapp_sent_at'] = $lead->whatsapp_sent_at ?? now();
            $updateData['whatsapp_sent_by'] = $request->user()->name;
        } else {
            $updateData['whatsapp_sent_at'] = null;
            $updateData['whatsapp_sent_by'] = null;
        }

        if ($status === 'completed') {
            $updateData['status'] = 'discovery';
        }

        $lead->update($updateData);

        ActivityLog::record(
            $lead->business_id,
            $request->user()->id,
            'admin_consultation_whatsapp_updated',
            'ClientLead',
            $lead->id,
            [
                'whatsapp_status' => $status,
                'whatsapp_sent_by' => $lead->whatsapp_sent_by,
                'reminder_notes' => $lead->reminder_notes,
            ],
            $request->ip()
        );

        return response()->json([
            'status' => 'success',
            'message' => match ($status) {
                'sent_from_sekayasa' => "Pengingat ditandai: Sudah Terkirim di WhatsApp oleh SEKA GROUP ({$request->user()->name}).",
                'completed' => 'Konsultasi fitur ditandai telah selesai / terjawab.',
                default => 'Status pengingat WhatsApp dikembalikan ke Menunggu Respon.',
            },
            'data' => $lead->fresh(['consultations', 'user', 'business']),
        ]);
    }
}
