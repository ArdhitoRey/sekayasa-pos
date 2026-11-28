<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Inventory;
use App\Models\Outlet;
use App\Models\Product;
use App\Models\StockMovement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InventoryController extends Controller
{
    /**
     * Stock Monitoring (FR-016, FR-019)
     */
    public function index(Request $request)
    {
        $user = $request->user();
        if (!$user->canManageOperations()) {
            return response()->json(['status' => 'error', 'message' => 'Pemantauan stok hanya dapat diakses oleh Owner atau Admin Toko.'], 403);
        }
        if ($user->role === 'admin' && $user->business) {
            $settings = $user->business->resolvedFeatureSettings();
            if (empty($settings['admin_manage_inventory'])) {
                return response()->json(['status' => 'error', 'message' => 'Pemilik bisnis menonaktifkan akses pemantauan stok untuk Admin.'], 403);
            }
        }
        $outletId = $request->query('outlet_id', $user->outlet_id);
        if ($outletId) {
            Outlet::where('business_id', $user->business_id)->findOrFail($outletId);
        }

        $products = Product::where('business_id', $user->business_id)
            ->where('is_active', true)
            ->with(['category', 'unit'])
            ->get()
            ->map(function ($product) use ($outletId, $user) {
                $inv = Inventory::where('outlet_id', $outletId)
                    ->where('product_id', $product->id)
                    ->first();
                $stock = $inv ? $inv->stock_quantity : 0;
                $product->current_stock = $stock;
                $product->is_low_stock = $stock <= $product->min_stock_alert;
                if ($user->role !== 'owner') {
                    $product->makeHidden('buy_price');
                }
                return $product;
            });

        if ($request->boolean('low_stock_only')) {
            $products = $products->filter(fn($p) => $p->is_low_stock)->values();
        }

        return response()->json([
            'status' => 'success',
            'data' => $products,
        ]);
    }

    /**
     * Stock Adjustment with Audit Reason (FR-017, FR-018)
     */
    public function adjustStock(Request $request)
    {
        $user = $request->user();
        if (!$user->canManageOperations()) {
            return response()->json(['status' => 'error', 'message' => 'Hanya Owner atau Admin yang dapat menyesuaikan stok.'], 403);
        }
        if ($user->role === 'admin' && $user->business) {
            $settings = $user->business->resolvedFeatureSettings();
            if (empty($settings['admin_manage_inventory'])) {
                return response()->json(['status' => 'error', 'message' => 'Pemilik bisnis menonaktifkan akses penyesuaian stok untuk Admin.'], 403);
            }
        }
        $validated = $request->validate([
            'outlet_id' => 'required|exists:outlets,id',
            'product_id' => 'required|exists:products,id',
            'type' => 'required|in:adjustment_add,adjustment_sub',
            'quantity' => 'required|integer|min:1',
            'reason' => 'required|string|min:3',
        ]);

        $outlet = Outlet::where('business_id', $user->business_id)->findOrFail($validated['outlet_id']);
        if (!$outlet->is_active) {
            return response()->json(['status' => 'error', 'message' => 'Outlet tujuan sedang dinonaktifkan.'], 422);
        }
        Product::where('business_id', $user->business_id)->where('is_active', true)->findOrFail($validated['product_id']);

        return DB::transaction(function () use ($user, $validated, $request) {
            $inventory = Inventory::where('outlet_id', $validated['outlet_id'])
                ->where('product_id', $validated['product_id'])
                ->lockForUpdate()
                ->first();

            if (!$inventory) {
                try {
                    Inventory::create([
                        'business_id' => $user->business_id,
                        'outlet_id' => $validated['outlet_id'],
                        'product_id' => $validated['product_id'],
                        'stock_quantity' => 0,
                    ]);
                } catch (\Illuminate\Database\QueryException $e) {
                    // Concurrent worker created row first; fall through to lock
                }
                $inventory = Inventory::where('outlet_id', $validated['outlet_id'])
                    ->where('product_id', $validated['product_id'])
                    ->lockForUpdate()
                    ->firstOrFail();
            }

            $before = $inventory->stock_quantity;
            $qty = $validated['quantity'];

            if ($validated['type'] === 'adjustment_add') {
                $after = $before + $qty;
            } else {
                $after = max(0, $before - $qty);
            }
            $change = $after - $before;

            $inventory->update(['stock_quantity' => $after]);

            $movement = StockMovement::create([
                'business_id' => $user->business_id,
                'outlet_id' => $validated['outlet_id'],
                'product_id' => $validated['product_id'],
                'type' => $validated['type'],
                'quantity_before' => $before,
                'quantity_change' => $change,
                'quantity_after' => $after,
                'reason' => $validated['reason'],
                'user_id' => $user->id,
            ]);

            ActivityLog::record(
                $user->business_id,
                $user->id,
                'inventory_adjusted',
                'StockMovement',
                $movement->id,
                [
                    'outlet_id' => $validated['outlet_id'],
                    'product_id' => $validated['product_id'],
                    'type' => $validated['type'],
                    'change' => $change,
                    'before' => $before,
                    'after' => $after,
                    'reason' => $validated['reason'],
                ],
                $request->ip()
            );

            return response()->json([
                'status' => 'success',
                'message' => 'Penyesuaian stok berhasil disimpan',
                'data' => [
                    'inventory' => $inventory,
                    'movement' => $movement,
                ],
            ]);
        });
    }

    /**
     * Stock Transfer Between Outlets (FR-020)
     */
    public function transferStock(Request $request)
    {
        $user = $request->user();
        if (!$user->canManageOperations()) {
            return response()->json(['status' => 'error', 'message' => 'Hanya Owner atau Admin yang dapat melakukan transfer stok antar-outlet.'], 403);
        }
        if ($user->role === 'admin' && $user->business) {
            $settings = $user->business->resolvedFeatureSettings();
            if (empty($settings['admin_manage_inventory'])) {
                return response()->json(['status' => 'error', 'message' => 'Pemilik bisnis menonaktifkan akses transfer stok untuk Admin.'], 403);
            }
        }
        $validated = $request->validate([
            'from_outlet_id' => 'required|exists:outlets,id',
            'to_outlet_id' => 'required|exists:outlets,id|different:from_outlet_id',
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|integer|min:1',
            'reason' => 'nullable|string',
        ]);

        $fromOutlet = Outlet::where('business_id', $user->business_id)->findOrFail($validated['from_outlet_id']);
        $toOutlet = Outlet::where('business_id', $user->business_id)->findOrFail($validated['to_outlet_id']);
        if (!$fromOutlet->is_active || !$toOutlet->is_active) {
            return response()->json(['status' => 'error', 'message' => 'Outlet asal atau tujuan sedang dinonaktifkan.'], 422);
        }
        Product::where('business_id', $user->business_id)->where('is_active', true)->findOrFail($validated['product_id']);

        return DB::transaction(function () use ($user, $validated, $request) {
            $fromOutletId = (int) $validated['from_outlet_id'];
            $toOutletId = (int) $validated['to_outlet_id'];
            $productId = (int) $validated['product_id'];

            // Lock destination and source inventory rows in deterministic outlet ID order to prevent deadlocks
            $orderedOutlets = [$fromOutletId, $toOutletId];
            sort($orderedOutlets);
            $lockedRows = [];
            foreach ($orderedOutlets as $oid) {
                $row = Inventory::where('outlet_id', $oid)
                    ->where('product_id', $productId)
                    ->lockForUpdate()
                    ->first();
                if (!$row && $oid === $toOutletId) {
                    try {
                        Inventory::create([
                            'business_id' => $user->business_id,
                            'outlet_id' => $toOutletId,
                            'product_id' => $productId,
                            'stock_quantity' => 0,
                        ]);
                    } catch (\Illuminate\Database\QueryException $e) {
                        // Concurrent worker inserted first
                    }
                    $row = Inventory::where('outlet_id', $toOutletId)
                        ->where('product_id', $productId)
                        ->lockForUpdate()
                        ->firstOrFail();
                }
                $lockedRows[$oid] = $row;
            }

            $fromInv = $lockedRows[$fromOutletId] ?? null;
            $qty = $validated['quantity'];

            if (!$fromInv || $fromInv->stock_quantity < $qty) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Stok di outlet asal tidak mencukupi untuk transfer.',
                ], 400);
            }

            // Deduct Source
            $beforeFrom = $fromInv->stock_quantity;
            $afterFrom = $beforeFrom - $qty;
            $fromInv->update(['stock_quantity' => $afterFrom]);

            StockMovement::create([
                'business_id' => $user->business_id,
                'outlet_id' => $fromOutletId,
                'product_id' => $productId,
                'type' => 'transfer_out',
                'quantity_before' => $beforeFrom,
                'quantity_change' => -$qty,
                'quantity_after' => $afterFrom,
                'reason' => 'Transfer keluar ke outlet #' . $toOutletId,
                'user_id' => $user->id,
            ]);

            // Add Destination (already locked inside transaction)
            $toInv = $lockedRows[$toOutletId];
            $beforeTo = $toInv->stock_quantity;
            $afterTo = $beforeTo + $qty;
            $toInv->update(['stock_quantity' => $afterTo]);

            StockMovement::create([
                'business_id' => $user->business_id,
                'outlet_id' => $validated['to_outlet_id'],
                'product_id' => $validated['product_id'],
                'type' => 'transfer_in',
                'quantity_before' => $beforeTo,
                'quantity_change' => $qty,
                'quantity_after' => $afterTo,
                'reason' => 'Transfer masuk dari outlet #' . $validated['from_outlet_id'],
                'user_id' => $user->id,
            ]);

            ActivityLog::record(
                $user->business_id,
                $user->id,
                'inventory_transferred',
                'Product',
                (int) $validated['product_id'],
                [
                    'from_outlet_id' => $validated['from_outlet_id'],
                    'to_outlet_id' => $validated['to_outlet_id'],
                    'quantity' => $qty,
                    'reason' => $validated['reason'] ?? null,
                ],
                $request->ip()
            );

            return response()->json([
                'status' => 'success',
                'message' => 'Transfer stok antar-outlet berhasil.',
            ]);
        });
    }

    /**
     * Stock Movement Audit Trail (FR-018)
     */
    public function movements(Request $request)
    {
        $user = $request->user();
        if (!$user->canManageOperations()) {
            return response()->json(['status' => 'error', 'message' => 'Riwayat mutasi stok hanya dapat diakses oleh Owner atau Admin Toko.'], 403);
        }
        if ($user->role === 'admin' && $user->business) {
            $settings = $user->business->resolvedFeatureSettings();
            if (empty($settings['admin_manage_inventory'])) {
                return response()->json(['status' => 'error', 'message' => 'Pemilik bisnis menonaktifkan akses riwayat mutasi stok untuk Admin.'], 403);
            }
        }
        $outletId = $request->query('outlet_id');
        if ($outletId) {
            Outlet::where('business_id', $user->business_id)->findOrFail($outletId);
        }

        $query = StockMovement::where('business_id', $user->business_id)
            ->with(['product', 'user', 'outlet'])
            ->latest();

        if ($outletId) {
            $query->where('outlet_id', $outletId);
        } elseif ($user->role !== 'owner' && $user->role !== 'admin' && $user->outlet_id) {
            $query->where('outlet_id', $user->outlet_id);
        }

        if ($productId = $request->query('product_id')) {
            $query->where('product_id', $productId);
        }

        $movements = $query->paginate(200);
        if ($user->role !== 'owner') {
            $movements->getCollection()->each(function ($mv) {
                if ($mv->product) {
                    $mv->product->makeHidden('buy_price');
                }
            });
        }

        return response()->json([
            'status' => 'success',
            'data' => $movements,
        ]);
    }
}
