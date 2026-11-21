<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Inventory;
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
        $outletId = $request->query('outlet_id', $user->outlet_id);

        $products = Product::where('business_id', $user->business_id)
            ->where('is_active', true)
            ->with(['category', 'unit'])
            ->get()
            ->map(function ($product) use ($outletId) {
                $inv = Inventory::where('outlet_id', $outletId)
                    ->where('product_id', $product->id)
                    ->first();
                $stock = $inv ? $inv->stock_quantity : 0;
                $product->current_stock = $stock;
                $product->is_low_stock = $stock <= $product->min_stock_alert;
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
        $validated = $request->validate([
            'outlet_id' => 'required|exists:outlets,id',
            'product_id' => 'required|exists:products,id',
            'type' => 'required|in:adjustment_add,adjustment_sub',
            'quantity' => 'required|integer|min:1',
            'reason' => 'required|string|min:3',
        ]);

        return DB::transaction(function () use ($user, $validated) {
            $inventory = Inventory::firstOrCreate(
                [
                    'outlet_id' => $validated['outlet_id'],
                    'product_id' => $validated['product_id'],
                ],
                [
                    'business_id' => $user->business_id,
                    'stock_quantity' => 0,
                ]
            );

            $before = $inventory->stock_quantity;
            $qty = $validated['quantity'];

            if ($validated['type'] === 'adjustment_add') {
                $after = $before + $qty;
                $change = $qty;
            } else {
                $after = max(0, $before - $qty);
                $change = -$qty;
            }

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
        $validated = $request->validate([
            'from_outlet_id' => 'required|exists:outlets,id',
            'to_outlet_id' => 'required|exists:outlets,id|different:from_outlet_id',
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|integer|min:1',
            'reason' => 'nullable|string',
        ]);

        return DB::transaction(function () use ($user, $validated) {
            $fromInv = Inventory::where('outlet_id', $validated['from_outlet_id'])
                ->where('product_id', $validated['product_id'])
                ->lockForUpdate()
                ->first();

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
                'outlet_id' => $validated['from_outlet_id'],
                'product_id' => $validated['product_id'],
                'type' => 'transfer_out',
                'quantity_before' => $beforeFrom,
                'quantity_change' => -$qty,
                'quantity_after' => $afterFrom,
                'reason' => 'Transfer keluar ke outlet #' . $validated['to_outlet_id'],
                'user_id' => $user->id,
            ]);

            // Add Destination
            $toInv = Inventory::firstOrCreate(
                [
                    'outlet_id' => $validated['to_outlet_id'],
                    'product_id' => $validated['product_id'],
                ],
                [
                    'business_id' => $user->business_id,
                    'stock_quantity' => 0,
                ]
            );

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
        $outletId = $request->query('outlet_id', $user->outlet_id);

        $query = StockMovement::where('business_id', $user->business_id)
            ->with(['product', 'user', 'outlet'])
            ->latest();

        if ($outletId) {
            $query->where('outlet_id', $outletId);
        }

        if ($productId = $request->query('product_id')) {
            $query->where('product_id', $productId);
        }

        $movements = $query->paginate(25);

        return response()->json([
            'status' => 'success',
            'data' => $movements,
        ]);
    }
}
