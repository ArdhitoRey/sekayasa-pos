<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Business;
use App\Models\Category;
use App\Models\Inventory;
use App\Models\Outlet;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\Unit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ProductController extends Controller
{
    /**
     * List Products with category, unit, and outlet inventory (FR-009, FR-014)
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $businessId = $user->business_id;
        if (!$businessId) {
            return response()->json(['status' => 'error', 'message' => 'Akses produk hanya untuk akun bisnis.'], 403);
        }
        $outletId = $request->query('outlet_id', $user->outlet_id);
        if ($user->role === 'cashier') {
            if (!$user->outlet_id) {
                return response()->json(['status' => 'error', 'message' => 'Akun kasir belum ditugaskan ke outlet.'], 403);
            }
            if ($outletId && (int) $outletId !== (int) $user->outlet_id) {
                return response()->json(['status' => 'error', 'message' => 'Kasir hanya dapat melihat produk pada outlet tempat bertugas.'], 403);
            }
            $outletId = $user->outlet_id;
        }
        if ($outletId) {
            Outlet::where('business_id', $businessId)->findOrFail($outletId);
        }

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

        $products = $query->get()->map(function ($product) use ($outletId, $user) {
            $stock = 0;
            if ($outletId) {
                $inv = Inventory::where('outlet_id', $outletId)
                    ->where('product_id', $product->id)
                    ->first();
                $stock = $inv ? $inv->stock_quantity : 0;
            }
            $product->current_stock = $stock;
            $product->is_low_stock = $stock <= $product->min_stock_alert;
            if ($user->role !== 'owner') {
                $product->makeHidden('buy_price');
            }
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
        if (!$user->canManageOperations()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Hanya Pemilik (Owner) atau Admin Toko yang dapat menambah produk.',
            ], 403);
        }
        if ($user->role === 'admin' && $user->business) {
            $settings = $user->business->resolvedFeatureSettings();
            if (empty($settings['admin_manage_products'])) {
                return response()->json(['status' => 'error', 'message' => 'Pemilik bisnis menonaktifkan akses tambah produk untuk Admin.'], 403);
            }
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'category_id' => 'nullable|exists:categories,id',
            'unit_id' => 'nullable|exists:units,id',
            'sku' => 'nullable|string|max:50',
            'barcode' => 'nullable|string|max:50',
            'buy_price' => ($user->role === 'owner' ? 'required|numeric|min:0' : 'nullable|numeric|min:0'),
            'sell_price' => 'required|numeric|min:0',
            'min_stock_alert' => 'nullable|integer|min:0',
            'initial_stock' => 'nullable|integer|min:0',
            'outlet_id' => 'nullable|exists:outlets,id',
            'image' => 'required|image|mimes:jpg,jpeg,png,webp|max:2048',
        ], [
            'image.required' => 'Gambar produk wajib diunggah agar kasir mudah mengenali menu.',
            'image.image' => 'File produk harus berupa gambar (JPG, PNG, atau WEBP).',
        ]);

        if (!empty($validated['outlet_id'])) {
            Outlet::where('business_id', $user->business_id)->findOrFail($validated['outlet_id']);
        }
        if (!empty($validated['category_id'])) {
            Category::where('business_id', $user->business_id)->findOrFail($validated['category_id']);
        }
        if (!empty($validated['unit_id'])) {
            Unit::where('business_id', $user->business_id)->findOrFail($validated['unit_id']);
        }

        $imagePath = $request->file('image')->store('products', 'public');

        try {
            return DB::transaction(function () use ($user, $validated, $imagePath, $request) {
                $business = Business::where('id', $user->business_id)->lockForUpdate()->first();
                $limits = $business ? $business->planLimits() : ['max_products' => 150, 'plan_name' => 'Default'];
                $currentCount = Product::where('business_id', $user->business_id)->where('is_active', true)->count();
                if ($currentCount >= $limits['max_products']) {
                    Storage::disk('public')->delete($imagePath);
                    return response()->json([
                        'status' => 'error',
                        'message' => "Batas maksimal produk untuk paket {$limits['plan_name']} ({$limits['max_products']} produk) telah tercapai.",
                    ], 403);
                }

                $product = Product::create([
                    'business_id' => $user->business_id,
                    'category_id' => $validated['category_id'] ?? null,
                    'unit_id' => $validated['unit_id'] ?? null,
                    'name' => $validated['name'],
                    'sku' => $validated['sku'] ?? null,
                    'barcode' => $validated['barcode'] ?? null,
                    'buy_price' => $user->role === 'owner' ? $validated['buy_price'] : 0,
                    'sell_price' => $validated['sell_price'],
                    'min_stock_alert' => $validated['min_stock_alert'] ?? 5,
                    'image_path' => $imagePath,
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

                ActivityLog::record(
                    $user->business_id,
                    $user->id,
                    'product_created',
                    'Product',
                    $product->id,
                    ['name' => $product->name, 'sell_price' => (float) $product->sell_price],
                    $request->ip()
                );

                $loaded = $product->load('category', 'unit');
                if ($user->role !== 'owner') {
                    $loaded->makeHidden('buy_price');
                }

                return response()->json([
                    'status' => 'success',
                    'message' => 'Produk berhasil ditambahkan',
                    'data' => $loaded,
                ], 201);
            });
        } catch (\Throwable $e) {
            Storage::disk('public')->delete($imagePath);
            throw $e;
        }
    }

    /**
     * Update Product (FR-011)
     */
    public function update(Request $request, $id)
    {
        $user = $request->user();
        if (!$user->canManageOperations()) {
            return response()->json(['status' => 'error', 'message' => 'Hanya Owner atau Admin Toko yang dapat mengubah produk.'], 403);
        }
        if ($user->role === 'admin' && $user->business) {
            $settings = $user->business->resolvedFeatureSettings();
            if (empty($settings['admin_manage_products'])) {
                return response()->json(['status' => 'error', 'message' => 'Pemilik bisnis menonaktifkan akses ubah produk untuk Admin.'], 403);
            }
        }

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'category_id' => 'nullable|exists:categories,id',
            'unit_id' => 'nullable|exists:units,id',
            'sku' => 'nullable|string|max:50',
            'barcode' => 'nullable|string|max:50',
            'buy_price' => 'sometimes|nullable|numeric|min:0',
            'sell_price' => 'sometimes|required|numeric|min:0',
            'min_stock_alert' => 'nullable|integer|min:0',
            'is_active' => 'boolean',
            'image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
        ]);

        $data = collect($validated)->except(['image'])->toArray();
        // Admin cannot view or overwrite supplier HPP (buy_price)
        if ($user->role !== 'owner') {
            unset($data['buy_price']);
        } elseif (array_key_exists('buy_price', $data) && $data['buy_price'] === null) {
            unset($data['buy_price']);
        }
        if (array_key_exists('category_id', $validated)) {
            if (!empty($validated['category_id'])) {
                Category::where('business_id', $user->business_id)->findOrFail($validated['category_id']);
            } else {
                $data['category_id'] = null;
            }
        }
        if (!empty($validated['unit_id'])) {
            Unit::where('business_id', $user->business_id)->findOrFail($validated['unit_id']);
        }

        return DB::transaction(function () use ($user, $id, $validated, &$data, $request) {
            $business = Business::where('id', $user->business_id)->lockForUpdate()->first();
            $product = Product::where('business_id', $user->business_id)->lockForUpdate()->findOrFail($id);

            if (!empty($validated['is_active']) && !$product->is_active) {
                $limits = $business ? $business->planLimits() : ['max_products' => 150, 'plan_name' => 'Default'];
                $activeOthers = Product::where('business_id', $user->business_id)
                    ->where('is_active', true)
                    ->where('id', '!=', $product->id)
                    ->count();
                if ($activeOthers >= $limits['max_products']) {
                    return response()->json([
                        'status' => 'error',
                        'message' => "Batas maksimal produk untuk paket {$limits['plan_name']} ({$limits['max_products']} produk) telah tercapai.",
                    ], 403);
                }
            }

            if ($request->hasFile('image')) {
                if ($product->image_path && !str_starts_with($product->image_path, '/assets/')) {
                    Storage::disk('public')->delete($product->image_path);
                }
                $data['image_path'] = $request->file('image')->store('products', 'public');
            }

            $product->update($data);
            $loaded = $product->load('category', 'unit');
            if ($user->role !== 'owner') {
                $loaded->makeHidden('buy_price');
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Produk berhasil diperbarui',
                'data' => $loaded,
            ]);
        });
    }

    /**
     * Archive/Delete Product (FR-012)
     */
    public function destroy(Request $request, $id)
    {
        $user = $request->user();
        if (!$user->canManageOperations()) {
            return response()->json(['status' => 'error', 'message' => 'Hanya Owner atau Admin Toko yang dapat mengarsipkan produk.'], 403);
        }
        if ($user->role === 'admin' && $user->business) {
            $settings = $user->business->resolvedFeatureSettings();
            if (empty($settings['admin_manage_products'])) {
                return response()->json(['status' => 'error', 'message' => 'Pemilik bisnis menonaktifkan akses arsip produk untuk Admin.'], 403);
            }
        }

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
        if (!$user->business_id) {
            return response()->json(['status' => 'error', 'message' => 'Akses produk hanya untuk akun bisnis.'], 403);
        }
        $outletId = $request->query('outlet_id', $user->outlet_id);
        $outlet = null;
        if ($outletId) {
            $outlet = Outlet::where('business_id', $user->business_id)->findOrFail($outletId);
            if ($user->role === 'cashier' && $user->outlet_id && (int) $outlet->id !== (int) $user->outlet_id) {
                return response()->json(['status' => 'error', 'message' => 'Kasir hanya dapat memeriksa barcode pada outlet tempat bertugas.'], 403);
            }
        } elseif ($user->role === 'cashier' && $user->outlet_id) {
            $outlet = Outlet::where('business_id', $user->business_id)->find($user->outlet_id);
            $outletId = $outlet?->id;
        }

        if ($user->role === 'cashier' && $user->business) {
            $settings = $user->business->resolvedFeatureSettings($outlet);
            if (empty($settings['pos_allow_barcode'])) {
                return response()->json(['status' => 'error', 'message' => 'Pemilik bisnis menonaktifkan fitur scan barcode pada outlet ini.'], 403);
            }
        }

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
        if ($user->role !== 'owner') {
            $product->makeHidden('buy_price');
        }

        return response()->json([
            'status' => 'success',
            'data' => $product,
        ]);
    }
}
