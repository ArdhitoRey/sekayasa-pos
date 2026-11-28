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
use App\Http\Controllers\Api\StaffController;
use App\Http\Middleware\EnsureActiveSubscription;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public Routes
|--------------------------------------------------------------------------
*/
Route::post('/email/send-verification', [AuthController::class, 'sendRegistrationVerification'])->middleware('throttle:30,1');
Route::post('/antibot/challenge', [AuthController::class, 'createAntibotChallenge'])->middleware('throttle:60,1');
Route::post('/antibot/verify', [AuthController::class, 'verifyAntibotChallenge'])->middleware('throttle:30,1');
Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:20,1');
Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:20,1');
Route::post('/password/forgot', [AuthController::class, 'forgotPassword'])->middleware('throttle:15,1');
Route::post('/password/reset', [AuthController::class, 'resetPassword'])->middleware('throttle:15,1');
Route::get('/auth/google/exchange/{nonce}', [AuthController::class, 'exchangeGoogleToken'])->middleware('throttle:20,1');
Route::post('/payments/midtrans-callback', [\App\Http\Controllers\Api\ClientAccountController::class, 'handleMidtransCallback'])->middleware('throttle:60,1');

/*
|--------------------------------------------------------------------------
| Protected Multi-Tenant API Routes (Sanctum)
|--------------------------------------------------------------------------
*/
Route::middleware('auth:sanctum')->group(function () {
    // Auth Profile
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);

    // Operational Store, Staff, Inventory, Shift, POS & Reports (Subscription-Gated)
    Route::middleware(EnsureActiveSubscription::class)->group(function () {
        // Outlets & Staff Management (Owner exclusive for mutations)
        Route::get('/outlets', [OutletController::class, 'index']);
        Route::post('/outlets', [OutletController::class, 'store']);
        Route::put('/outlets/{id}', [OutletController::class, 'update']);

        Route::get('/staff', [StaffController::class, 'index']);
        Route::post('/staff', [StaffController::class, 'store']);
        Route::put('/staff/{id}', [StaffController::class, 'update']);
        Route::delete('/staff/{id}', [StaffController::class, 'destroy']);
        Route::put('/business/profile', [StaffController::class, 'updateBusinessProfile']);
        Route::put('/business/settings', [StaffController::class, 'updateFeatureSettings']);

        // Categories (FR-013)
        Route::get('/categories', [CategoryController::class, 'index']);
        Route::post('/categories', [CategoryController::class, 'store']);
        Route::put('/categories/{id}', [CategoryController::class, 'update']);
        Route::delete('/categories/{id}', [CategoryController::class, 'destroy']);

        // Products (FR-009 s/d FR-015)
        Route::get('/products', [ProductController::class, 'index']);
        Route::post('/products', [ProductController::class, 'store']);
        Route::post('/products/{id}', [ProductController::class, 'update']);
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
    });

    // Client Account Portal (Wajib Login/Register untuk Sewa Layanan atau Konsultasi Custom)
    Route::prefix('client')->group(function () {
        Route::get('/overview', [\App\Http\Controllers\Api\ClientAccountController::class, 'overview']);
        Route::post('/rent-template', [\App\Http\Controllers\Api\ClientAccountController::class, 'rentTemplate']);
        Route::get('/payments/{paymentId}/status', [\App\Http\Controllers\Api\ClientAccountController::class, 'checkPaymentStatus']);
        Route::get('/payments/{paymentId}/receipt', [\App\Http\Controllers\Api\ClientAccountController::class, 'paymentReceipt']);
        Route::post('/consultations', [\App\Http\Controllers\Api\ClientAccountController::class, 'requestConsultation']);
        Route::post('/uat/{testCaseId}', [\App\Http\Controllers\Api\ClientAccountController::class, 'submitOwnUat']);
    });

    // Super Admin Platform Management (Monitoring Semua Klien, Aktivasi Uji Coba & Pelacakan Error)
    Route::prefix('admin')->group(function () {
        Route::get('/platform-overview', [\App\Http\Controllers\Api\AdminController::class, 'overview']);
        Route::get('/businesses/{id}', [\App\Http\Controllers\Api\AdminController::class, 'businessDetail']);
        Route::post('/businesses/{id}/extend-subscription', [\App\Http\Controllers\Api\AdminController::class, 'extendSubscription']);
        Route::post('/subscription-payments/{id}/verify', [\App\Http\Controllers\Api\AdminController::class, 'verifySubscriptionPayment']);
        Route::post('/consultations/{id}/whatsapp-status', [\App\Http\Controllers\Api\AdminController::class, 'updateConsultationWhatsappStatus']);
        Route::post('/businesses/{id}/toggle-status', [\App\Http\Controllers\Api\AdminController::class, 'toggleBusinessStatus']);
        Route::delete('/businesses/{id}', [\App\Http\Controllers\Api\AdminController::class, 'destroyBusiness']);
    });
});
