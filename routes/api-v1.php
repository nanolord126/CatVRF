<?php declare(strict_types=1);

use Illuminate\Support\Facades\Route;

/**
 * CatVRF API v1 Routes Registry — Production 2026
 * Endpoint: /api/v1/
 * Version: 2026.03.25
 *
 * Middleware Pipeline:
 * - auth:sanctum (validate API token)
 * - tenant (validate & scope tenant)
 * - rate-limit (per-endpoint throttling, tenant-aware)
 * - fraud-check (on payment endpoints only)
 *
 * Each vertical maintains its own route definitions for modularity.
 */

// ===== API v1 with Authentication & Tenant Middleware =====
Route::prefix('v1')
    ->middleware([
        'auth:sanctum',    // Validate Sanctum API token
        'tenant',          // Validate & scope tenant
        'rate-limit',      // Per-endpoint throttling (tenant-aware)
    ])
    ->group(function () {
        
        // Include all vertical-specific route files
        require base_path('routes/beauty.api.php');
        require base_path('routes/food.api.php');
        require base_path('routes/hotels.api.php');
        require base_path('routes/auto.api.php');
        require base_path('routes/payment.api.php');
        require base_path('routes/promo.api.php');
        require base_path('routes/referral.api.php');
        require base_path('routes/wallet.api.php');

    });

// ===== WEBHOOK ROUTES (No Auth, Signature-based) =====
Route::prefix('webhooks')
    ->middleware(['webhook-signature'])
    ->group(function () {
        
        // Tinkoff Payment Gateway webhooks
        Route::post('/tinkoff/payment-notification', function () {
            return response()->json(['status' => 'ok']);
        })->name('webhooks.tinkoff.payment');

        // Tochka Bank webhooks
        Route::post('/tochka/payment-notification', function () {
            return response()->json(['status' => 'ok']);
        })->name('webhooks.tochka.payment');

        // Sber Payment Gateway webhooks
        Route::post('/sber/payment-notification', function () {
            return response()->json(['status' => 'ok']);
        })->name('webhooks.sber.payment');

    });
