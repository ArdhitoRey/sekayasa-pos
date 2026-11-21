<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Inventory;
use App\Models\Product;
use App\Models\StockMovement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProductController extends Controller
{
    /**
     * List Products with category, unit, and outlet inventory (FR-009, FR-014)
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $businessId = $user->business_id;
        $outletId = $request->query('outlet_id', $user->outlet_id);

        $query = Product::where('business_id', $businessId)
            ->with(['category', 'unit'])
            ->where('is_active', true);

        // Search by name, SKU, or barcode (FR-014)
        if ($search = $request->query('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('sku', 'like', "%{$search}%")
                  ->orWhere('barcode', 'like', "%{$search}%");
            });
        }

        // Filter by category
        if ($catId = $request->query('category_id')) {
            $query->where('category_id', $catId);
        }

        $products = $query->get()->map(function ($product) use ($outletId) {
            $stock = 0;
            if ($outletId) {
                $inv = Inventory::where('outlet_id', $outletId)
                    ->where('product_id', $product->id)
                    ->first();
                $stock = $inv ? $inv->stock_quantity : 0;
            }
            $product->current_stock = $stock;
            $product->is_low_stock = $stock <= $product->min_stock_alert;
            return $product;
        });

        return response()->json([
            'status' => 'success',
            'data' => $products,
        ]);
    }

    /**
     * Create Product (FR-010)
     */
    public function store(Request $request)
    {
        $user = $request->user();
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'category_id' => 'nullable|exists:categories,id',
            'unit_id' => 'nullable|exists:units,id',
            'sku' => 'nullable|string|max:50',
            'barcode' => 'nullable|string|max:50',
            'buy_price' => 'required|numeric|min:0',
            'sell_price' => 'required|numeric|min:0',
            'min_stock_alert' => 'nullable|integer|min:0',
            'initial_stock' => 'nullable|integer|min:0',
            'outlet_id' => 'nullable|exists:outlets,id',
        ]);

        return DB::transaction(function () use ($user, $validated) {
            $product = Product::create([
                'business_id' => $user->business_id,
                'category_id' => $validated['category_id'] ?? null,
                'unit_id' => $validated['unit_id'] ?? null,
                'name' => $validated['name'],
                'sku' => $validated['sku'] ?? null,
                'barcode' => $validated['barcode'] ?? null,
                'buy_price' => $validated['buy_price'],
                'sell_price' => $validated['sell_price'],
                'min_stock_alert' => $validated['min_stock_alert'] ?? 5,
                'is_active' => true,
            ]);

            // Set initial stock if provided
            $outletId = $validated['outlet_id'] ?? $user->outlet_id;
            if ($outletId && isset($validated['initial_stock']) && $validated['initial_stock'] > 0) {
                Inventory::updateOrCreate(
                    ['outlet_id' => $outletId, 'product_id' => $product->id],
                    ['business_id' => $user->business_id, 'stock_quantity' => $validated['initial_stock']]
                );

                StockMovement::create([
                    'business_id' => $user->business_id,
                    'outlet_id' => $outletId,
                    'product_id' => $product->id,
                    'type' => 'adjustment_add',
                    'quantity_before' => 0,
                    'quantity_change' => $validated['initial_stock'],
                    'quantity_after' => $validated['initial_stock'],
                    'reason' => 'Stok Awal Produk Baru',
                    'user_id' => $user->id,
                ]);
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Produk berhasil ditambahkan',
                'data' => $product->load('category', 'unit'),
            ], 201);
        });
    }

    /**
     * Update Product (FR-011)
     */
    public function update(Request $request, $id)
    {
        $user = $request->user();
        $product = Product::where('business_id', $user->business_id)->findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'category_id' => 'nullable|exists:categories,id',
            'unit_id' => 'nullable|exists:units,id',
            'sku' => 'nullable|string|max:50',
            'barcode' => 'nullable|string|max:50',
            'buy_price' => 'required|numeric|min:0',
            'sell_price' => 'required|numeric|min:0',
            'min_stock_alert' => 'nullable|integer|min:0',
            'is_active' => 'boolean',
        ]);

        $product->update($validated);

        return response()->json([
            'status' => 'success',
            'message' => 'Produk berhasil diperbarui',
            'data' => $product->load('category', 'unit'),
        ]);
    }

    /**
     * Archive/Delete Product (FR-012)
     */
    public function destroy(Request $request, $id)
    {
        $user = $request->user();
        $product = Product::where('business_id', $user->business_id)->findOrFail($id);
        $product->update(['is_active' => false]);

        return response()->json([
            'status' => 'success',
            'message' => 'Produk berhasil diarsipkan',
        ]);
    }

    /**
     * Barcode Quick Search (FR-024)
     */
    public function findByBarcode(Request $request, $barcode)
    {
        $user = $request->user();
        $outletId = $request->query('outlet_id', $user->outlet_id);

        $product = Product::where('business_id', $user->business_id)
            ->where('barcode', $barcode)
            ->where('is_active', true)
            ->with(['category', 'unit'])
            ->first();

        if (!$product) {
            return response()->json([
                'status' => 'error',
                'message' => 'Produk dengan barcode ' . $barcode . ' tidak ditemukan',
            ], 404);
        }

        $inv = Inventory::where('outlet_id', $outletId)->where('product_id', $product->id)->first();
        $product->current_stock = $inv ? $inv->stock_quantity : 0;

        return response()->json([
            'status' => 'success',
            'data' => $product,
        ]);
    }
}
