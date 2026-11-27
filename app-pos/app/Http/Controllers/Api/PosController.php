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

        $outlet = Outlet::where('business_id', $businessId)->findOrFail($outletId);
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
            return DB::transaction(function () use ($user, $businessId, $outletId, $validated, $request, $activeShift, $outlet, $business) {
                $shift = $activeShift;

            // Calculate Subtotal & Verify Stock atomically
            $subtotal = 0;
            $itemsToProcess = [];

            foreach ($validated['items'] as $itemData) {
                // Lock stock row for update (prevent race conditions)
                $inventory = Inventory::where('outlet_id', $outletId)
                    ->where('product_id', $itemData['product_id'])
                    ->lockForUpdate()
                    ->first();

                $product = Product::where('business_id', $businessId)->findOrFail($itemData['product_id']);

                if (!$inventory || $inventory->stock_quantity < $itemData['quantity']) {
                    $available = $inventory ? $inventory->stock_quantity : 0;
                    throw new \Exception("Stok tidak mencukupi untuk '{$product->name}'. Tersedia: {$available}, Diminta: {$itemData['quantity']}");
                }

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
                    'quantity' => $itemData['quantity'],
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
            $taxAmount = ($taxableAmount * $taxPercentage) / 100;
            $totalAmount = round($taxableAmount + $taxAmount);

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
                'discount_amount' => $validated['discount_amount'] ?? 0,
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

            return response()->json([
                'status' => 'success',
                'message' => 'Transaksi berhasil diproses',
                'data' => [
                    'transaction' => $transaction->load(['items', 'payment', 'customer', 'outlet', 'cashier']),
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
            'notes' => 'nullable|string',
        ]);

        Outlet::where('business_id', $user->business_id)->findOrFail($validated['outlet_id']);
        if (!empty($validated['customer_id'])) {
            Customer::where('business_id', $user->business_id)->findOrFail($validated['customer_id']);
        }

        $invoiceNumber = "HOLD-" . strtoupper(Str::random(6));

        $transaction = Transaction::create([
            'business_id' => $user->business_id,
            'outlet_id' => $validated['outlet_id'],
            'cashier_id' => $user->id,
            'customer_id' => $validated['customer_id'] ?? null,
            'invoice_number' => $invoiceNumber,
            'subtotal' => 0,
            'total_amount' => 0,
            'payment_status' => 'held',
            'notes' => json_encode([
                'items' => $validated['items'],
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
        $outletId = $request->query('outlet_id', $user->outlet_id);
        if ($outletId) {
            Outlet::where('business_id', $user->business_id)->findOrFail($outletId);
        }

        $helds = Transaction::where('business_id', $user->business_id)
            ->where('outlet_id', $outletId)
            ->where('payment_status', 'held')
            ->latest()
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $helds,
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
        $transaction = Transaction::where('business_id', $user->business_id)
            ->where('payment_status', 'held')
            ->findOrFail($id);
        $data = json_decode($transaction->notes, true);
        $transaction->delete(); // Remove hold entry

        return response()->json([
            'status' => 'success',
            'data' => $data,
        ]);
    }

    /**
     * Transaction History (FR-032)
     */
    public function history(Request $request)
    {
        $user = $request->user();
        $outletId = $request->query('outlet_id', $user->outlet_id);
        if ($outletId) {
            Outlet::where('business_id', $user->business_id)->findOrFail($outletId);
        }

        $query = Transaction::where('business_id', $user->business_id)
            ->where('outlet_id', $outletId)
            ->where('payment_status', '!=', 'held')
            ->with(['items', 'payment', 'customer', 'cashier', 'outlet'])
            ->latest();

        if ($date = $request->query('date')) {
            $query->whereDate('created_at', $date);
        }

        $transactions = $query->paginate(100);

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
        $transaction = Transaction::where('business_id', $request->user()->business_id)
            ->with(['items', 'payment', 'customer', 'outlet', 'cashier'])
            ->findOrFail($id);

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
                ->findOrFail($id);

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

            // Restore Stock
            foreach ($transaction->items as $item) {
                $inv = Inventory::where('outlet_id', $transaction->outlet_id)
                    ->where('product_id', $item->product_id)
                    ->lockForUpdate()
                    ->first();

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

            // Reconcile open shift drawer if transaction belongs to an active shift
            if ($transaction->shift_id && $transaction->payment) {
                $openShift = CashierShift::where('id', $transaction->shift_id)
                    ->where('status', 'open')
                    ->first();
                if ($openShift) {
                    if ($transaction->payment->payment_method === 'cash') {
                        $openShift->decrement('cash_sales_total', $transaction->total_amount);
                    } else {
                        $openShift->decrement('non_cash_sales_total', $transaction->total_amount);
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
