<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CategoryController extends Controller
{
    private function authorizeCategoryAccess(Request $request): void
    {
        $user = $request->user();
        if (!$user || !$user->canManageOperations()) {
            abort(403, 'Hanya Owner atau Admin yang diizinkan mengelola kategori.');
        }

        if ($user->role === 'admin' && $user->business) {
            $settings = $user->business->resolvedFeatureSettings();
            if (empty($settings['admin_manage_categories'])) {
                abort(403, 'Pemilik bisnis menonaktifkan akses kelola kategori untuk Admin.');
            }
        }
    }

    public function index(Request $request)
    {
        $user = $request->user();
        if (!$user->business_id) {
            return response()->json([
                'status' => 'error',
                'message' => 'Akses kategori hanya tersedia untuk akun bisnis.',
            ], 403);
        }

        $categories = Category::where('business_id', $user->business_id)
            ->with(['products' => function ($q) {
                $q->where('is_active', true)
                    ->select('id', 'business_id', 'category_id', 'name', 'sku', 'sell_price', 'image_path')
                    ->orderBy('name');
            }])
            ->withCount(['products' => fn ($q) => $q->where('is_active', true)])
            ->orderBy('name')
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $categories,
        ]);
    }

    public function store(Request $request)
    {
        $this->authorizeCategoryAccess($request);
        $user = $request->user();
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:500',
        ]);

        $nameTrimmed = trim($validated['name']);
        $exists = Category::where('business_id', $user->business_id)
            ->whereRaw('LOWER(name) = ?', [strtolower($nameTrimmed)])
            ->exists();
        if ($exists) {
            return response()->json([
                'status' => 'error',
                'message' => "Kategori '{$nameTrimmed}' sudah ada.",
            ], 422);
        }

        $category = Category::create([
            'business_id' => $user->business_id,
            'name' => $nameTrimmed,
            'slug' => Str::slug($nameTrimmed) . '-' . substr(md5($nameTrimmed . microtime()), 0, 4),
            'description' => $validated['description'] ?? null,
        ]);

        ActivityLog::record(
            $user->business_id,
            $user->id,
            'category_created',
            'Category',
            $category->id,
            ['name' => $category->name],
            $request->ip()
        );

        return response()->json([
            'status' => 'success',
            'message' => 'Kategori berhasil ditambahkan',
            'data' => $category->load(['products' => fn ($q) => $q->where('is_active', true)])->loadCount(['products' => fn ($q) => $q->where('is_active', true)]),
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $this->authorizeCategoryAccess($request);
        $user = $request->user();
        $category = Category::where('business_id', $user->business_id)->findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:500',
        ]);

        $nameTrimmed = trim($validated['name']);
        $duplicate = Category::where('business_id', $user->business_id)
            ->where('id', '!=', $category->id)
            ->whereRaw('LOWER(name) = ?', [strtolower($nameTrimmed)])
            ->exists();
        if ($duplicate) {
            return response()->json([
                'status' => 'error',
                'message' => "Kategori '{$nameTrimmed}' sudah digunakan.",
            ], 422);
        }

        $category->update([
            'name' => $nameTrimmed,
            'slug' => Str::slug($nameTrimmed),
            'description' => $validated['description'] ?? null,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Kategori berhasil diperbarui',
            'data' => $category->load(['products' => fn ($q) => $q->where('is_active', true)])->loadCount(['products' => fn ($q) => $q->where('is_active', true)]),
        ]);
    }

    public function destroy(Request $request, $id)
    {
        $this->authorizeCategoryAccess($request);
        $user = $request->user();
        $category = Category::where('business_id', $user->business_id)->findOrFail($id);

        Product::where('business_id', $user->business_id)
            ->where('category_id', $category->id)
            ->update(['category_id' => null]);

        $catName = $category->name;
        $category->delete();

        ActivityLog::record(
            $user->business_id,
            $user->id,
            'category_deleted',
            'Category',
            (int) $id,
            ['name' => $catName],
            $request->ip()
        );

        return response()->json([
            'status' => 'success',
            'message' => 'Kategori berhasil dihapus',
        ]);
    }
}
