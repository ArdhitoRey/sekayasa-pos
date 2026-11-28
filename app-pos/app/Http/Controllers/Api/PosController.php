<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Business;
use App\Models\CashierShift;
use App\Models\Customer;
use App\Models\Inventory;
use App\Models\Outlet;
use App\Models\Payment;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\Transaction;
use App\Models\TransactionItem;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PosController extends Controller
{
    private function ensureCashier(Request $request)
    {
        $user = $request->user();
        if (!$user || $user->role !== 'cashier') {
            return response()->json([
                'status' => 'error',
                'code' => 'CASHIER_ROLE_REQUIRED',
                'message' => 'Transaksi kasir (Checkout / Hold / Void) hanya dapat dilakukan oleh akun Kasir (Cashier). Akun Owner hanya dapat membuka Mode Preview (Read-Only).',
            ], 403);
        }

        return null;
    }

    /**
     * Resolve and authorize target outlet within caller's business, locking cashiers strictly to their assigned branch.
     */
    private function resolveAuthorizedOutlet(User $user, mixed $requestedOutletId, bool $requireOutlet = true): array
    {
        $hasRequested = $requestedOutletId !== null && $requestedOutletId !== '';

        if ($user->role === 'cashier') {
            if ($hasRequested) {
                $requestedOutlet = Outlet::where('business_id', $user->business_id)->findOrFail($requestedOutletId);
                if (!$user->outlet_id || (int) $requestedOutlet->id !== (int) $user->outlet_id) {
                    return [null, response()->json([
                        'status' => 'error',
                        'code' => 'OUTLET_ACCESS_DENIED',
                        'message' => 'Anda hanya dapat beroperasi pada outlet tempat Anda ditugaskan.',
                    ], 403)];
                }

                return [$requestedOutlet, null];
            }

            if (!$user->outlet_id) {
                return [null, response()->json([
                    'status' => 'error',
                    'code' => 'OUTLET_ACCESS_DENIED',
                    'message' => 'Akun kasir belum ditetapkan ke outlet mana pun.',
                ], 403)];
            }

            return [Outlet::where('business_id', $user->business_id)->findOrFail($user->outlet_id), null];
        }

        $targetId = $hasRequested ? $requestedOutletId : ($requireOutlet ? $user->outlet_id : null);
        if (!$targetId) {
            if ($requireOutlet) {
                return [null, response()->json([
                    'status' => 'error',
                    'message' => 'Outlet tidak ditentukan.',
                ], 422)];
            }

            return [null, null];
        }

        return [Outlet::where('business_id', $user->business_id)->findOrFail($targetId), null];
    }

    /**
     * Process POS Checkout (FR-021 s/d FR-030)
     * Includes atomic stock locking & shift accumulation
     */
    public function checkout(Request $request)
    {
        if ($resp = $this->ensureCashier($request)) {
            return $resp;
        }

        $user = $request->user();
        $businessId = $user->business_id;
        $outletId = $request->input('outlet_id', $user->outlet_id);

        $validated = $request->validate([
            'outlet_id' => 'required|exists:outlets,id',
            'customer_id' => 'nullable|exists:customers,id',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.discount_amount' => 'nullable|numeric|min:0',
            'discount_type' => 'nullable|in:fixed,percent',
            'discount_amount' => 'nullable|numeric|min:0',
            'tax_percentage' => 'nullable|numeric|min:0',
            'payment_method' => 'required|in:cash,transfer,qris,ewallet',
            'amount_paid' => 'required|numeric|min:0',
            'reference_number' => 'nullable|string|max:100',
            'notes' => 'nullable|string',
        ]);

        [$outlet, $outletErr] = $this->resolveAuthorizedOutlet($user, $validated['outlet_id']);
        if ($outletErr) {
            return $outletErr;
        }
        if (!$outlet->is_active) {
            return response()->json([
                'status' => 'error',
                'message' => 'Outlet ini sedang dinonaktifkan oleh pemilik bisnis.',
            ], 422);
        }
        $outletId = $outlet->id;
        if (!empty($validated['customer_id'])) {
            Customer::where('business_id', $businessId)->findOrFail($validated['customer_id']);
        }
        $business = Business::findOrFail($businessId);
        $effectiveSettings = $business->resolvedFeatureSettings($outlet);

        if ($user->role === 'cashier' && empty($effectiveSettings['pos_allow_discount']) && (float) ($validated['discount_amount'] ?? 0) > 0) {
            return response()->json([
                'status' => 'error',
                'message' => 'Pemilik bisnis menonaktifkan fitur pemberian diskon untuk kasir pada outlet ini.',
            ], 403);
        }

        // Require an open cashier shift when enabled (default true) so every transaction reconciles to a cash drawer
        $activeShift = CashierShift::where('outlet_id', $outletId)
            ->where('user_id', $user->id)
            ->where('status', 'open')
            ->latest('opened_at')
            ->first();

        if (!empty($effectiveSettings['pos_require_shift']) && !$activeShift) {
            ActivityLog::record(
                $businessId,
                $user->id,
                'pos_checkout_failed',
                'Outlet',
                (int) $outletId,
                ['error' => 'Shift kasir belum dibuka sebelum checkout.'],
                $request->ip()
            );

            return response()->json([
                'status' => 'error',
                'code' => 'SHIFT_NOT_OPEN',
                'message' => 'Shift kasir belum dibuka. Silakan klik "Buka Shift" dan isi kas awal laci terlebih dahulu agar data transaksi dan kas tercatat lengkap.',
            ], 422);
        }

        try {
            return DB::transaction(function () use ($user, $businessId, $outletId, $validated, $request, $activeShift, $outlet, $business, $effectiveSettings) {
                $shift = null;
                if ($activeShift) {
                    $shift = CashierShift::whereKey($activeShift->id)
                        ->where('outlet_id', $outletId)
                        ->where('user_id', $user->id)
                        ->where('status', 'open')
                        ->lockForUpdate()
                        ->first();
                }

                if (!empty($effectiveSettings['pos_require_shift']) && !$shift) {
                    return response()->json([
                        'status' => 'error',
                        'code' => 'SHIFT_NOT_OPEN',
                        'message' => 'Shift kasir belum dibuka. Silakan klik "Buka Shift" dan isi kas awal laci terlebih dahulu agar data transaksi dan kas tercatat lengkap.',
                    ], 422);
                }

                if ($user->role === 'cashier') {
                    if (!empty($validated['customer_id']) && empty($effectiveSettings['pos_allow_customer'])) {
                        return response()->json([
                            'status' => 'error',
                            'message' => 'Pemilik bisnis menonaktifkan fitur pemilihan pelanggan untuk kasir pada outlet ini.',
                        ], 403);
                    }
                    if (!empty(trim((string) ($validated['notes'] ?? ''))) && empty($effectiveSettings['pos_allow_notes'])) {
                        return response()->json([
                            'status' => 'error',
                            'message' => 'Pemilik bisnis menonaktifkan fitur catatan pesanan untuk kasir pada outlet ini.',
                        ], 403);
                    }
                }

                // Aggregate cumulative quantities and lock inventory rows in deterministic ID order (prevents duplicate product_id bypass & deadlocks)
                $qtyPerProduct = [];
                $productIds = [];
                foreach ($validated['items'] as $itemData) {
                    $pid = (int) $itemData['product_id'];
                    $qty = (int) $itemData['quantity'];
                    $qtyPerProduct[$pid] = ($qtyPerProduct[$pid] ?? 0) + $qty;
                    $productIds[$pid] = $pid;
                }
                ksort($productIds);

                $lockedProducts = [];
                $lockedInventories = [];
                foreach ($productIds as $pid) {
                    $lockedProducts[$pid] = Product::where('business_id', $businessId)
                        ->where('is_active', true)
                        ->findOrFail($pid);
                    $lockedInventories[$pid] = Inventory::where('outlet_id', $outletId)
                        ->where('product_id', $pid)
                        ->lockForUpdate()
                        ->first();

                    $inv = $lockedInventories[$pid];
                    $prod = $lockedProducts[$pid];
                    $needed = $qtyPerProduct[$pid];
                    if (!$inv || $inv->stock_quantity < $needed) {
                        $available = $inv ? $inv->stock_quantity : 0;
                        throw new \Exception("Stok tidak mencukupi untuk '{$prod->name}'. Tersedia: {$available}, Diminta: {$needed}");
                    }
                }

                // Calculate Subtotal & Validate Line Items
                $subtotal = 0;
                $itemsToProcess = [];

                foreach ($validated['items'] as $itemData) {
                    $pid = (int) $itemData['product_id'];
                    $product = $lockedProducts[$pid];
                    $inventory = $lockedInventories[$pid];

                    $rawItemDiscount = (float) ($itemData['discount_amount'] ?? 0);
                    if ($user->role === 'cashier' && empty($effectiveSettings['pos_allow_discount']) && $rawItemDiscount > 0) {
                        throw new \Exception('Pemilik bisnis menonaktifkan fitur pemberian diskon untuk kasir pada outlet ini.');
                    }
                    $lineGross = (float) $product->sell_price * (int) $itemData['quantity'];
                    $itemDiscount = min(max(0, $rawItemDiscount), $lineGross);
                    $lineSubtotal = $lineGross - $itemDiscount;
                    $subtotal += $lineSubtotal;

                    $itemsToProcess[] = [
                        'product' => $product,
                        'inventory' => $inventory,
                        'quantity' => (int) $itemData['quantity'],
                        'discount_amount' => $itemDiscount,
                        'line_subtotal' => $lineSubtotal,
                    ];
                }

            // Calculate Bill Discount (clamped against negative / >100% abuse)
            $discountTotal = 0;
            if (!empty($validated['discount_type']) && !empty($validated['discount_amount'])) {
                if ($validated['discount_type'] === 'percent') {
                    $pct = min(100, max(0, (float) $validated['discount_amount']));
                    $discountTotal = ($subtotal * $pct) / 100;
                } else {
                    $discountTotal = min(max(0, (float) $validated['discount_amount']), $subtotal);
                }
            }
            $discountTotal = min($discountTotal, $subtotal);

            // Calculate Tax strictly from authoritative Business / Branch rule to prevent client tax tampering
            $taxPercentage = $user->role === 'cashier'
                ? (float) $outlet->effectiveTaxPercentage($business)
                : min(100, max(0, (float) ($validated['tax_percentage'] ?? $outlet->effectiveTaxPercentage($business))));
            $taxableAmount = max(0, $subtotal - $discountTotal);
            $taxAmount = round(($taxableAmount * $taxPercentage) / 100);
            $totalAmount = round($taxableAmount + $taxAmount);

            $storedDiscountAmount = 0;
            if (!empty($validated['discount_type']) && !empty($validated['discount_amount'])) {
                $storedDiscountAmount = $validated['discount_type'] === 'percent'
                    ? min(100, max(0, (float) $validated['discount_amount']))
                    : $discountTotal;
            }

            // Validate Payment
            $amountPaid = $validated['amount_paid'];
            if ($amountPaid < $totalAmount) {
                throw new \Exception("Jumlah pembayaran (Rp " . number_format($amountPaid, 0, ',', '.') . ") kurang dari total belanja (Rp " . number_format($totalAmount, 0, ',', '.') . ")");
            }

            $changeAmount = $validated['payment_method'] === 'cash' ? max(0, $amountPaid - $totalAmount) : 0;

            // Generate Unique Invoice Number (INV-OUTLET-Ymd-XXXX)
            $datePrefix = date('Ymd');
            $uniqueCode = strtoupper(Str::random(4));
            $invoiceNumber = "INV-{$outletId}-{$datePrefix}-{$uniqueCode}";

            // 1. Create Transaction
            $transaction = Transaction::create([
                'business_id' => $businessId,
                'outlet_id' => $outletId,
                'cashier_id' => $user->id,
                'shift_id' => $shift ? $shift->id : null,
                'customer_id' => $validated['customer_id'] ?? null,
                'invoice_number' => $invoiceNumber,
                'subtotal' => $subtotal,
                'discount_type' => $validated['discount_type'] ?? null,
                'discount_amount' => $storedDiscountAmount,
                'discount_total' => $discountTotal,
                'tax_percentage' => $taxPercentage,
                'tax_amount' => $taxAmount,
                'total_amount' => $totalAmount,
                'payment_status' => 'paid',
                'notes' => $validated['notes'] ?? null,
            ]);

            // 2. Insert Items & Deduct Stock with Movement Log
            foreach ($itemsToProcess as $item) {
                $product = $item['product'];
                $inventory = $item['inventory'];
                $qty = $item['quantity'];

                TransactionItem::create([
                    'transaction_id' => $transaction->id,
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'buy_price' => $product->buy_price,
                    'sell_price' => $product->sell_price,
                    'quantity' => $qty,
                    'discount_amount' => $item['discount_amount'],
                    'subtotal' => $item['line_subtotal'],
                ]);

                // Update Stock
                $beforeStock = $inventory->stock_quantity;
                $afterStock = $beforeStock - $qty;
                $inventory->update(['stock_quantity' => $afterStock]);

                // Stock Movement Record
                StockMovement::create([
                    'business_id' => $businessId,
                    'outlet_id' => $outletId,
                    'product_id' => $product->id,
                    'type' => 'sale',
                    'quantity_before' => $beforeStock,
                    'quantity_change' => -$qty,
                    'quantity_after' => $afterStock,
                    'reason' => "Penjualan Struk #{$invoiceNumber}",
                    'user_id' => $user->id,
                    'reference_type' => 'Transaction',
                    'reference_id' => $transaction->id,
                ]);
            }

            // 3. Record Payment
            $payment = Payment::create([
                'transaction_id' => $transaction->id,
                'payment_method' => $validated['payment_method'],
                'amount_paid' => $amountPaid,
                'change_amount' => $changeAmount,
                'reference_number' => $validated['reference_number'] ?? null,
            ]);

            // 4. Update Shift Totals
            if ($shift) {
                if ($validated['payment_method'] === 'cash') {
                    $shift->increment('cash_sales_total', $totalAmount);
                } else {
                    $shift->increment('non_cash_sales_total', $totalAmount);
                }
            }

            ActivityLog::record(
                $businessId,
                $user->id,
                'pos_checkout',
                'Transaction',
                $transaction->id,
                [
                    'invoice_number' => $transaction->invoice_number,
                    'outlet_id' => (int) $outletId,
                    'payment_method' => $validated['payment_method'],
                    'items_count' => count($itemsToProcess),
                    'total_amount' => (float) $totalAmount,
                ],
                $request->ip()
            );

            $loadedTransaction = $transaction->load(['items', 'payment', 'customer', 'outlet', 'cashier']);
            if ($user->role !== 'owner' && $loadedTransaction->items) {
                $loadedTransaction->items->each->makeHidden('buy_price');
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Transaksi berhasil diproses',
                'data' => [
                    'transaction' => $loadedTransaction,
                    'receipt' => [
                        'invoice_number' => $transaction->invoice_number,
                        'date' => $transaction->created_at->format('d/m/Y H:i'),
                        'business_name' => $business->name,
                        'outlet_name' => $transaction->outlet->name,
                        'outlet_address' => $transaction->outlet->address,
                        'outlet_phone' => $transaction->outlet->phone,
                        'cashier_name' => $user->name,
                        'customer_name' => $transaction->customer ? $transaction->customer->name : 'Pelanggan Umum',
                        'customer_phone' => $transaction->customer ? $transaction->customer->phone : null,
                        'notes' => $transaction->notes,
                        'items' => $transaction->items->map(function ($it) {
                            return [
                                'name' => $it->product_name,
                                'qty' => $it->quantity,
                                'price' => $it->sell_price,
                                'subtotal' => $it->subtotal,
                            ];
                        }),
                        'subtotal' => $subtotal,
                        'discount_total' => $discountTotal,
                        'tax_amount' => $taxAmount,
                        'total_amount' => $totalAmount,
                        'payment_method' => strtoupper($validated['payment_method']),
                        'reference_number' => $payment->reference_number,
                        'amount_paid' => $amountPaid,
                        'change_amount' => $changeAmount,
                        'tax_percentage' => (float) $taxPercentage,
                        'receipt_header' => $outlet->receipt_header,
                        'footer' => $outlet->effectiveReceiptFooter($business),
                    ],
                ],
            ], 201);
            });
        } catch (\Throwable $e) {
            ActivityLog::record(
                $businessId,
                $user->id,
                'pos_checkout_failed',
                'Outlet',
                (int) $outletId,
                [
                    'error' => mb_substr($e->getMessage(), 0, 260),
                    'payment_method' => $validated['payment_method'] ?? null,
                    'items_count' => count($validated['items'] ?? []),
                ],
                $request->ip()
            );

            throw $e;
        }
    }

    /**
     * Hold Transaction (FR-027)
     */
    public function holdTransaction(Request $request)
    {
        if ($resp = $this->ensureCashier($request)) {
            return $resp;
        }

        $user = $request->user();
        $validated = $request->validate([
            'outlet_id' => 'required|exists:outlets,id',
            'customer_id' => 'nullable|exists:customers,id',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|integer',
            'items.*.quantity' => 'required|integer|min:1',
            'notes' => 'nullable|string|max:500',
        ]);

        [$outlet, $outletErr] = $this->resolveAuthorizedOutlet($user, $validated['outlet_id']);
        if ($outletErr) {
            return $outletErr;
        }
        if (!$outlet->is_active) {
            return response()->json([
                'status' => 'error',
                'message' => 'Outlet ini sedang dinonaktifkan oleh pemilik bisnis.',
            ], 422);
        }
        if ($user->role === 'cashier' && $user->business) {
            $settings = $user->business->resolvedFeatureSettings($outlet);
            if (!empty($validated['customer_id']) && empty($settings['pos_allow_customer'])) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Pemilik bisnis menonaktifkan fitur pemilihan pelanggan pada outlet ini.',
                ], 403);
            }
            if (!empty(trim((string) ($validated['notes'] ?? ''))) && empty($settings['pos_allow_notes'])) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Pemilik bisnis menonaktifkan fitur catatan pesanan pada outlet ini.',
                ], 403);
            }
        }
        if (!empty($validated['customer_id'])) {
            Customer::where('business_id', $user->business_id)->findOrFail($validated['customer_id']);
        }

        // Validate every held item belongs to an active product of this tenant
        $requestedPids = collect($validated['items'])->pluck('product_id')->unique()->values();
        $productsById = Product::where('business_id', $user->business_id)
            ->where('is_active', true)
            ->whereIn('id', $requestedPids)
            ->get()
            ->keyBy('id');

        if ($productsById->count() !== $requestedPids->count()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Sebagian produk dalam keranjang tidak valid atau sudah diarsipkan.',
            ], 422);
        }

        $estimatedTotal = 0;
        $normalizedItems = [];
        foreach ($validated['items'] as $entry) {
            $prod = $productsById->get((int) $entry['product_id']);
            $qty = (int) $entry['quantity'];
            $estimatedTotal += ((float) $prod->sell_price) * $qty;
            $normalizedItems[] = [
                'product_id' => $prod->id,
                'product_name' => $prod->name,
                'quantity' => $qty,
            ];
        }

        $invoiceNumber = "HOLD-" . strtoupper(Str::random(6));

        $transaction = Transaction::create([
            'business_id' => $user->business_id,
            'outlet_id' => $outlet->id,
            'cashier_id' => $user->id,
            'customer_id' => $validated['customer_id'] ?? null,
            'invoice_number' => $invoiceNumber,
            'subtotal' => $estimatedTotal,
            'total_amount' => $estimatedTotal,
            'payment_status' => 'held',
            'notes' => json_encode([
                'items' => $normalizedItems,
                'user_notes' => $validated['notes'] ?? '',
            ]),
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Transaksi berhasil ditahan',
            'data' => $transaction,
        ]);
    }

    /**
     * List Held Transactions (FR-027)
     */
    public function listHeld(Request $request)
    {
        $user = $request->user();
        [$outlet, $outletErr] = $this->resolveAuthorizedOutlet($user, $request->query('outlet_id'), false);
        if ($outletErr) {
            return $outletErr;
        }

        $query = Transaction::where('business_id', $user->business_id)
            ->where('payment_status', 'held')
            ->with('customer:id,name,phone')
            ->latest();

        if ($outlet) {
            $query->where('outlet_id', $outlet->id);
        }

        $held = $query->get()->map(function ($txn) {
            $decoded = json_decode((string) $txn->notes, true);
            $items = is_array($decoded['items'] ?? null) ? $decoded['items'] : [];
            $userNotes = is_string($decoded['user_notes'] ?? null) ? $decoded['user_notes'] : '';
            $txn->setAttribute('cart_payload', $items);
            $txn->setAttribute('items', collect($items)->map(fn ($it) => [
                'product_id' => $it['product_id'] ?? null,
                'product_name' => $it['product_name'] ?? ('Produk #' . ($it['product_id'] ?? '?')),
                'quantity' => $it['quantity'] ?? 1,
            ])->values());
            $txn->notes = $userNotes;
            return $txn;
        });

        return response()->json([
            'status' => 'success',
            'data' => $held,
        ]);
    }

    /**
     * Resume / Cancel Held Transaction (FR-027)
     */
    public function resumeHeld(Request $request, $id)
    {
        if ($resp = $this->ensureCashier($request)) {
            return $resp;
        }

        $user = $request->user();

        return DB::transaction(function () use ($user, $id) {
            $transaction = Transaction::where('business_id', $user->business_id)
                ->where('payment_status', 'held')
                ->with('outlet')
                ->lockForUpdate()
                ->findOrFail($id);

            if (!$user->outlet_id || (int) $transaction->outlet_id !== (int) $user->outlet_id) {
                return response()->json([
                    'status' => 'error',
                    'code' => 'OUTLET_ACCESS_DENIED',
                    'message' => 'Anda hanya dapat mengambil transaksi tertahan pada outlet tempat Anda ditugaskan.',
                ], 403);
            }

            $data = json_decode($transaction->notes, true) ?: [];
            $data['customer_id'] = $transaction->customer_id;
            $data['notes'] = $data['user_notes'] ?? '';
            $data['cart_payload'] = $data['items'] ?? [];
            $transaction->delete(); // Remove hold entry atomically inside lock

            return response()->json([
                'status' => 'success',
                'data' => $data,
            ]);
        });
    }

    /**
     * Transaction History (FR-032)
     */
    public function history(Request $request)
    {
        $user = $request->user();
        [$outlet, $outletErr] = $this->resolveAuthorizedOutlet($user, $request->query('outlet_id'), false);
        if ($outletErr) {
            return $outletErr;
        }

        $query = Transaction::where('business_id', $user->business_id)
            ->where('payment_status', '!=', 'held')
            ->with(['items', 'payment', 'customer', 'cashier', 'outlet'])
            ->latest();

        if ($outlet) {
            if ($user->role === 'cashier' && $user->business) {
                $settings = $user->business->resolvedFeatureSettings($outlet);
                if (empty($settings['pos_allow_history'])) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Pemilik bisnis menonaktifkan akses riwayat transaksi untuk kasir pada outlet ini.',
                    ], 403);
                }
            }
            $query->where('outlet_id', $outlet->id);
        } elseif ($user->role === 'cashier' && $user->business) {
            $settings = $user->business->resolvedFeatureSettings($user->outlet);
            if (empty($settings['pos_allow_history'])) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Pemilik bisnis menonaktifkan akses riwayat transaksi untuk kasir pada outlet ini.',
                ], 403);
            }
        }

        if ($date = $request->query('date')) {
            $query->whereBetween('created_at', [
                \Carbon\Carbon::parse($date)->startOfDay(),
                \Carbon\Carbon::parse($date)->endOfDay(),
            ]);
        }

        $transactions = $query->paginate(100);
        if ($user->role !== 'owner') {
            $transactions->getCollection()->each(function ($txn) {
                if ($txn->relationLoaded('items') && $txn->items) {
                    $txn->items->each->makeHidden('buy_price');
                }
            });
        }

        return response()->json([
            'status' => 'success',
            'data' => $transactions,
        ]);
    }

    /**
     * Transaction Detail (FR-033)
     */
    public function detail(Request $request, $id)
    {
        $user = $request->user();
        $transaction = Transaction::where('business_id', $user->business_id)
            ->with(['items', 'payment', 'customer', 'outlet', 'cashier'])
            ->findOrFail($id);

        if ($user->role === 'cashier' && (!$user->outlet_id || (int) $transaction->outlet_id !== (int) $user->outlet_id)) {
            return response()->json([
                'status' => 'error',
                'code' => 'OUTLET_ACCESS_DENIED',
                'message' => 'Anda hanya dapat melihat detail transaksi pada outlet tempat Anda ditugaskan.',
            ], 403);
        }

        if ($user->role === 'cashier' && $user->business) {
            $settings = $user->business->resolvedFeatureSettings($transaction->outlet);
            if (empty($settings['pos_allow_history'])) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Pemilik bisnis menonaktifkan akses detail riwayat transaksi untuk kasir pada outlet ini.',
                ], 403);
            }
        }

        if ($user->role !== 'owner' && $transaction->relationLoaded('items') && $transaction->items) {
            $transaction->items->each->makeHidden('buy_price');
        }

        return response()->json([
            'status' => 'success',
            'data' => $transaction,
        ]);
    }

    /**
     * Void / Refund Transaction (FR-034)
     */
    public function voidTransaction(Request $request, $id)
    {
        if ($resp = $this->ensureCashier($request)) {
            return $resp;
        }

        $user = $request->user();
        $validated = $request->validate([
            'reason' => 'required|string|min:5',
        ]);

        return DB::transaction(function () use ($user, $id, $validated, $request) {
            $transaction = Transaction::where('business_id', $user->business_id)
                ->with(['items', 'payment', 'outlet'])
                ->lockForUpdate()
                ->findOrFail($id);

            if (!$user->outlet_id || (int) $transaction->outlet_id !== (int) $user->outlet_id) {
                return response()->json([
                    'status' => 'error',
                    'code' => 'OUTLET_ACCESS_DENIED',
                    'message' => 'Anda hanya dapat membatalkan transaksi pada outlet tempat Anda ditugaskan.',
                ], 403);
            }

            if ($user->role === 'cashier' && $user->business) {
                $settings = $user->business->resolvedFeatureSettings($transaction->outlet);
                if (empty($settings['pos_allow_void'])) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Pemilik bisnis menonaktifkan hak pembatalan transaksi (Void) untuk kasir pada outlet ini.',
                    ], 403);
                }
            }

            if ($transaction->payment_status === 'voided') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Transaksi ini sudah pernah dibatalkan sebelumnya.',
                ], 400);
            }

            if ($transaction->payment_status !== 'paid') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Hanya transaksi yang sudah lunas (paid) yang dapat di-void.',
                ], 422);
            }

            // Lock all affected inventory rows in deterministic product ID order before restoring
            $voidPids = $transaction->items->pluck('product_id')->unique()->map(fn ($pid) => (int) $pid)->sort()->values();
            $lockedVoidInventories = [];
            foreach ($voidPids as $pid) {
                $lockedVoidInventories[$pid] = Inventory::where('outlet_id', $transaction->outlet_id)
                    ->where('product_id', $pid)
                    ->lockForUpdate()
                    ->first();
            }

            // Restore Stock atomically via shared locked instances
            foreach ($transaction->items as $item) {
                $inv = $lockedVoidInventories[(int) $item->product_id] ?? null;

                if ($inv) {
                    $before = $inv->stock_quantity;
                    $after = $before + $item->quantity;
                    $inv->update(['stock_quantity' => $after]);

                    StockMovement::create([
                        'business_id' => $user->business_id,
                        'outlet_id' => $transaction->outlet_id,
                        'product_id' => $item->product_id,
                        'type' => 'void',
                        'quantity_before' => $before,
                        'quantity_change' => $item->quantity,
                        'quantity_after' => $after,
                        'reason' => "Void Transaksi #{$transaction->invoice_number}: {$validated['reason']}",
                        'user_id' => $user->id,
                        'reference_type' => 'Transaction',
                        'reference_id' => $transaction->id,
                    ]);
                }
            }

            // Reconcile shift cash/non-cash drawer atomically with row lock
            if ($transaction->payment) {
                $reconciledShift = null;
                if ($transaction->shift_id) {
                    $reconciledShift = CashierShift::where('id', $transaction->shift_id)
                        ->where('status', 'open')
                        ->lockForUpdate()
                        ->first();
                }

                if ($reconciledShift) {
                    if ($transaction->payment->payment_method === 'cash') {
                        $reconciledShift->decrement('cash_sales_total', $transaction->total_amount);
                    } else {
                        $reconciledShift->decrement('non_cash_sales_total', $transaction->total_amount);
                    }
                } elseif ($transaction->payment->payment_method === 'cash') {
                    // Cash refund of an older/closed-shift sale must deduct from the cashier's currently active cash drawer at this outlet
                    $currentOpenShift = CashierShift::where('outlet_id', $transaction->outlet_id)
                        ->where('user_id', $user->id)
                        ->where('status', 'open')
                        ->latest('opened_at')
                        ->lockForUpdate()
                        ->first();
                    if ($currentOpenShift) {
                        $currentOpenShift->decrement('cash_sales_total', $transaction->total_amount);
                    }
                }
            }

            $transaction->update(['payment_status' => 'voided']);

            ActivityLog::record(
                $user->business_id,
                $user->id,
                'pos_void_transaction',
                'Transaction',
                $transaction->id,
                ['invoice_number' => $transaction->invoice_number, 'reason' => $validated['reason']],
                $request->ip()
            );

            return response()->json([
                'status' => 'success',
                'message' => 'Transaksi berhasil dibatalkan dan stok telah dikembalikan.',
            ]);
        });
    }
}
