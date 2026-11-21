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
});

/*
|--------------------------------------------------------------------------
| PRD v2: Portal Internal Layanan Custom & One-Click Deployment (Bab 7 & 10)
|--------------------------------------------------------------------------
*/
Route::prefix('portal')->group(function () {
    Route::get('/leads', [\App\Http\Controllers\Api\PortalController::class, 'index']);
    Route::post('/leads', [\App\Http\Controllers\Api\PortalController::class, 'store']);
    Route::get('/leads/{id}', [\App\Http\Controllers\Api\PortalController::class, 'show']);
    Route::post('/leads/{id}/status', [\App\Http\Controllers\Api\PortalController::class, 'updateStatus']);
    Route::post('/contracts/{id}/verify-payment', [\App\Http\Controllers\Api\PortalController::class, 'verifyPayment']);
    Route::post('/uat/{id}', [\App\Http\Controllers\Api\PortalController::class, 'submitUat']);
    Route::get('/subscription-payments', [\App\Http\Controllers\Api\PortalController::class, 'listSubscriptionPayments']);
    Route::post('/subscription-payments/{id}/verify', [\App\Http\Controllers\Api\PortalController::class, 'verifySubscriptionPayment']);
});

Route::prefix('deployment')->group(function () {
    Route::get('/{leadId}/prechecks', [\App\Http\Controllers\Api\DeploymentController::class, 'validatePreChecks']);
    Route::post('/{leadId}/deploy-staging', [\App\Http\Controllers\Api\DeploymentController::class, 'deployStaging']);
    Route::post('/{leadId}/deploy-production', [\App\Http\Controllers\Api\DeploymentController::class, 'deployProduction']);
    Route::post('/runs/{id}/rollback', [\App\Http\Controllers\Api\DeploymentController::class, 'rollback']);
});
