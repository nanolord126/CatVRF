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
        'correlation-id',     // 1. Generate/validate X-Correlation-ID (первым!)
        'enrich-context',     // 2. Add IP, user_agent metadata
        'auth:sanctum',       // 3. Validate Sanctum API token
        'tenant',             // 4. Validate & scope tenant
        'b2c-b2b',            // 5. B2C/B2B mode determination
        'rate-limit',         // 6. Per-endpoint throttling (tenant-aware)
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
        require base_path('routes/luxury.api.php');
        require base_path('routes/pd.api.php');
        require base_path('routes/ritual.api.php');
        require base_path('routes/geo_logistics.api.php');

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
