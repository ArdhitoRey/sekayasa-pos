<?php

use App\Models\Plan;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;

// Public Website Routes (Tandha by SEKA GROUP POS — Aligned with Business Process & Design System)
Route::get('/', function () {
    return view('site.home');
})->name('home');

Route::get('/fitur', function () {
    return view('site.fitur');
})->name('fitur');

Route::get('/harga', function () {
    $plans = collect();
    try {
        if (Schema::hasTable('plans')) {
            $plans = Plan::where('is_active', true)->orderBy('price_monthly')->get();
        }
    } catch (\Throwable $e) {
        $plans = collect();
    }

    if ($plans->isEmpty()) {
        $plans = collect([
            (object) [
                'id' => 1,
                'name' => 'Starter',
                'slug' => 'starter',
                'price_monthly' => 99000,
                'max_outlets' => 1,
                'max_users' => 2,
                'max_products' => 150,
                'features' => ['pos_mobile', 'basic_inventory', 'daily_report'],
            ],
            (object) [
                'id' => 2,
                'name' => 'Business',
                'slug' => 'business',
                'price_monthly' => 199000,
                'max_outlets' => 3,
                'max_users' => 6,
                'max_products' => 1000,
                'features' => ['pos_mobile', 'multi_outlet', 'shift_management', 'profit_report', 'stock_transfer'],
            ],
            (object) [
                'id' => 3,
                'name' => 'Pro Enterprise',
                'slug' => 'pro',
                'price_monthly' => 399000,
                'max_outlets' => 10,
                'max_users' => 25,
                'max_products' => 10000,
                'features' => ['all_features', 'api_access', 'priority_support'],
            ],
        ]);
    }

    return view('site.harga', compact('plans'));
})->name('harga');

Route::get('/about', function () {
    return view('site.about');
})->name('about');

Route::get('/contact', function () {
    return view('site.contact');
})->name('contact');

// Operational Applications
Route::get('/login', function () {
    return view('auth.login');
})->name('login');

Route::get('/auth/google', [\App\Http\Controllers\Api\AuthController::class, 'redirectToGoogle'])->name('auth.google');
Route::get('/auth/google/callback', [\App\Http\Controllers\Api\AuthController::class, 'handleGoogleCallback'])->name('auth.google.callback');

Route::get('/client', function () {
    return view('dashboard.index', ['initialTab' => 'billing']);
})->name('client');

Route::get('/pos', function () {
    return view('pos.index');
})->name('pos');

Route::get('/dashboard', function () {
    return view('dashboard.index');
})->name('dashboard');

Route::get('/download/kasir-apk', function () {
    $apkPath = public_path('downloads/tandha-kasir.apk');
    if (is_file($apkPath)) {
        return response()->download($apkPath, 'tandha-pos-kasir.apk', [
            'Content-Type' => 'application/vnd.android.package-archive',
        ]);
    }
    $externalUrl = (string) config('app.kasir_apk_download_url', '');
    if ($externalUrl !== '' && preg_match('#^https?://#i', $externalUrl) === 1) {
        return redirect()->away($externalUrl);
    }
    return redirect('/dashboard?tab=billing');
})->name('download.kasir_apk');
