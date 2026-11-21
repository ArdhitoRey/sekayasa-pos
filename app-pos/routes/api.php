<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\CustomerController;
use App\Http\Controllers\Api\InventoryController;
use App\Http\Controllers\Api\OutletController;
use App\Http\Controllers\Api\PosController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\ReportController;
use App\Http\Controllers\Api\ShiftController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public Routes
|--------------------------------------------------------------------------
*/
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

/*
|--------------------------------------------------------------------------
| Protected Multi-Tenant API Routes (Sanctum)
|--------------------------------------------------------------------------
*/
Route::middleware('auth:sanctum')->group(function () {
    // Auth Profile
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);

    // Outlets (FR-007, FR-008)
    Route::get('/outlets', [OutletController::class, 'index']);
    Route::post('/outlets', [OutletController::class, 'store']);
    Route::put('/outlets/{id}', [OutletController::class, 'update']);

    // Categories (FR-013)
    Route::get('/categories', [CategoryController::class, 'index']);
    Route::post('/categories', [CategoryController::class, 'store']);
    Route::put('/categories/{id}', [CategoryController::class, 'update']);
    Route::delete('/categories/{id}', [CategoryController::class, 'destroy']);

    // Products (FR-009 s/d FR-015)
    Route::get('/products', [ProductController::class, 'index']);
    Route::post('/products', [ProductController::class, 'store']);
    Route::put('/products/{id}', [ProductController::class, 'update']);
    Route::delete('/products/{id}', [ProductController::class, 'destroy']);
    Route::get('/products/barcode/{barcode}', [ProductController::class, 'findByBarcode']);

    // Inventory & Stock Management (FR-016 s/d FR-020)
    Route::get('/inventory', [InventoryController::class, 'index']);
    Route::post('/inventory/adjust', [InventoryController::class, 'adjustStock']);
    Route::post('/inventory/transfer', [InventoryController::class, 'transferStock']);
    Route::get('/inventory/movements', [InventoryController::class, 'movements']);

    // Cashier Shift Management (FR-035 s/d FR-037)
    Route::get('/shifts/current', [ShiftController::class, 'current']);
    Route::post('/shifts/open', [ShiftController::class, 'open']);
    Route::post('/shifts/{id}/close', [ShiftController::class, 'close']);
    Route::get('/shifts/history', [ShiftController::class, 'history']);

    // POS & Cashier Transactions (FR-021 s/d FR-034)
    Route::post('/pos/checkout', [PosController::class, 'checkout']);
    Route::post('/pos/hold', [PosController::class, 'holdTransaction']);
    Route::get('/pos/held', [PosController::class, 'listHeld']);
    Route::delete('/pos/held/{id}/resume', [PosController::class, 'resumeHeld']);
    Route::get('/pos/history', [PosController::class, 'history']);
    Route::get('/pos/transactions/{id}', [PosController::class, 'detail']);
    Route::post('/pos/transactions/{id}/void', [PosController::class, 'voidTransaction']);

    // Customers (FR-038 s/d FR-040)
    Route::get('/customers', [CustomerController::class, 'index']);
    Route::post('/customers', [CustomerController::class, 'store']);

    // Reports & Dashboard (FR-045 s/d FR-050)
    Route::get('/reports/dashboard', [ReportController::class, 'dashboard']);
    Route::get('/reports/sales', [ReportController::class, 'salesReport']);
    Route::get('/reports/profit', [ReportController::class, 'profitReport']);

    // Client Account Portal (Wajib Login/Register untuk Sewa Layanan atau Konsultasi Custom)
    Route::prefix('client')->group(function () {
        Route::get('/overview', [\App\Http\Controllers\Api\ClientAccountController::class, 'overview']);
        Route::post('/rent-template', [\App\Http\Controllers\Api\ClientAccountController::class, 'rentTemplate']);
        Route::get('/payments/{paymentId}/status', [\App\Http\Controllers\Api\ClientAccountController::class, 'checkPaymentStatus']);
        Route::post('/consultations', [\App\Http\Controllers\Api\ClientAccountController::class, 'requestConsultation']);
        Route::post('/uat/{testCaseId}', [\App\Http\Controllers\Api\ClientAccountController::class, 'submitOwnUat']);
    });

    // Super Admin Platform Management (Verifikasi Langganan & Pantau Konsultasi)
    Route::prefix('admin')->group(function () {
        Route::get('/platform-overview', [\App\Http\Controllers\Api\AdminController::class, 'overview']);
        Route::post('/subscription-payments/{id}/verify', [\App\Http\Controllers\Api\AdminController::class, 'verifySubscriptionPayment']);
    });
});
