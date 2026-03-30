<?php declare(strict_types=1);

use Illuminate\Support\Facades\Route;

/**
 * CatVRF API Routes — Production Ready 2026
 * Version: 2026.03.25
 * 
 * Middleware Pipeline (all requests):
 * 1. CorrelationIdMiddleware - inject/validate correlation_id
 * 2. EnrichRequestContextMiddleware - IP, user_agent, timing
 * 3. auth:sanctum - validate API token (except /health and /webhooks)
 * 4. TenantMiddleware - tenant scoping and validation
 * 5. RateLimitMiddleware - per-endpoint throttling (tenant-aware)
 * 6. FraudCheckMiddleware - payment fraud detection (payment endpoints only)
 * 7. WebhookSignatureMiddleware - HMAC validation (webhook endpoints only)
 */

// ===== GLOBAL MIDDLEWARE (all routes) =====
Route::middleware([
    'correlation-id',      // Inject/validate X-Correlation-ID header
    'enrich-context',      // Add IP, user_agent, timing metadata
])->group(function () {

    // ===== HEALTH CHECK (No Auth) =====
    Route::get('/health', function () {
        return response()->json(['status' => 'ok', 'timestamp' => now()]);
    })->name('api.health');

    // ===== API V1 PRODUCTION ROUTES =====
    // All routes defined in routes/api-v1.php with tenant + auth middleware
    require base_path('routes/api-v1.php');

// ===== LEGACY API V1 - Authenticated (Backward Compatibility) =====
Route::prefix('v1')
    ->middleware('auth:sanctum')
    ->group(function () {
        
        // Payment routes
        Route::prefix('payments')->group(function () {
            Route::post('/', [\App\Http\Controllers\Api\V1\PaymentController::class, 'store'])
                ->name('v1.payments.store');
            Route::get('{payment}', [\App\Http\Controllers\Api\V1\PaymentController::class, 'show'])
                ->name('v1.payments.show');
            Route::post('{payment}/refund', [\App\Http\Controllers\Api\V1\PaymentController::class, 'refund'])
                ->name('v1.payments.refund');
        });
        
        // Wallet routes
        Route::prefix('wallets')->group(function () {
            Route::get('/', [\App\Http\Controllers\Api\V1\WalletController::class, 'index'])
                ->name('v1.wallets.index');
            Route::get('{wallet}', [\App\Http\Controllers\Api\V1\WalletController::class, 'show'])
                ->name('v1.wallets.show');
            Route::post('{wallet}/deposit', [\App\Http\Controllers\Api\V1\WalletController::class, 'deposit'])
                ->name('v1.wallets.deposit');
            Route::post('{wallet}/withdraw', [\App\Http\Controllers\Api\V1\WalletController::class, 'withdraw'])
                ->name('v1.wallets.withdraw');
        });
        
        // Promo routes
        Route::prefix('promos')->group(function () {
            Route::post('apply', [\App\Http\Controllers\Api\V1\PromoController::class, 'apply'])
                ->name('v1.promos.apply');
        });
        
        // Search routes
        Route::prefix('search')->group(function () {
            Route::get('/', [\App\Http\Controllers\Api\V1\SearchController::class, 'index'])
                ->name('v1.search.index');
        });
    });

// API v2 - Future version
Route::prefix('v2')
    ->middleware('auth:sanctum')
    ->group(function () {
        Route::prefix('payments')->group(function () {
            Route::post('init', [\App\Http\Controllers\Api\V2\PaymentController::class, 'init'])
                ->name('v2.payments.init');
            Route::get('{payment}', [\App\Http\Controllers\Api\V2\PaymentController::class, 'show'])
                ->name('v2.payments.show');
        });
    });

// Webhook routes (IP whitelisted, no auth)
Route::prefix('webhooks')
    ->middleware([\App\Http\Middleware\IpWhitelistMiddleware::class])
    ->group(function () {
        Route::post('tinkoff', [\App\Http\Controllers\Internal\WebhookController::class, 'handleTinkoff'])
            ->name('webhooks.tinkoff');
        Route::post('sber', [\App\Http\Controllers\Internal\WebhookController::class, 'handleSber'])
            ->name('webhooks.sber');
        Route::post('sbp', [\App\Http\Controllers\Internal\WebhookController::class, 'handleSbp'])
            ->name('webhooks.sbp');
    });

// ─── B2B API Routes ───────────────────────────────────────────────────────
require __DIR__ . '/api/b2b.php';

// ─── Verticals API Routes ─────────────────────────────────────────────────────
// Beauty & Wellness
require __DIR__ . '/beauty.api.php';

// Food & Delivery
require __DIR__ . '/food.api.php';

// Hotels & Accommodation
require __DIR__ . '/hotels.api.php';

// Auto & Taxi & Services
require __DIR__ . '/auto.api.php';

// Real Estate
require __DIR__ . '/realestate.api.php';

// Courses & Education
require __DIR__ . '/courses.api.php';

// Medical & Healthcare
require __DIR__ . '/medical.api.php';

// Archived Art Artistic Services (legacy vertical, tenant + auth)
Route::prefix('archived/artistic-services')
    ->middleware(['auth:sanctum', 'tenant'])
    ->group(function (): void {
        Route::get('/', [\App\Domains\Archived\Art\ArtisticServices\Http\Controllers\ArtisticProjectController::class, 'index'])
            ->name('archived.artistic-services.index');
        Route::post('/', [\App\Domains\Archived\Art\ArtisticServices\Http\Controllers\ArtisticProjectController::class, 'store'])
            ->name('archived.artistic-services.store');
        Route::get('{projectId}', [\App\Domains\Archived\Art\ArtisticServices\Http\Controllers\ArtisticProjectController::class, 'show'])
            ->name('archived.artistic-services.show');
        Route::post('{projectId}/complete', [\App\Domains\Archived\Art\ArtisticServices\Http\Controllers\ArtisticProjectController::class, 'complete'])
            ->name('archived.artistic-services.complete');
        Route::post('{projectId}/cancel', [\App\Domains\Archived\Art\ArtisticServices\Http\Controllers\ArtisticProjectController::class, 'cancel'])
            ->name('archived.artistic-services.cancel');
    });

// Pet Services & Clinics
require __DIR__ . '/pet.api.php';

// Entertainment (Tickets, Events)
require __DIR__ . '/tickets.api.php';

// Travel & Tourism
require __DIR__ . '/travel.api.php';

// Sports & Fitness
require __DIR__ . '/sports.api.php';

// Freelance & Services
require __DIR__ . '/freelance.api.php';

// Photography & Video
require __DIR__ . '/photography.api.php';

// Logistics & Courier
require __DIR__ . '/logistics.api.php';

// Fresh Produce & Delivery
require __DIR__ . '/fresh_produce.api.php';

// Grocery Delivery
require __DIR__ . '/grocery.api.php';

// Pharmacy & Medical Supplies
require __DIR__ . '/pharmacy.api.php';

// Healthy Food & Diet
require __DIR__ . '/healthy_food.api.php';

// Confectionery & Bakery
require __DIR__ . '/confectionery.api.php';

// Meat Shops
require __DIR__ . '/meat_shops.api.php';

// Office Catering
require __DIR__ . '/office_catering.api.php';

// Farm Direct
require __DIR__ . '/farm_direct.api.php';

// Books & Literature
require __DIR__ . '/books.api.php';

// Cosmetics & Perfume
require __DIR__ . '/cosmetics.api.php';

// Jewelry
require __DIR__ . '/jewelry.api.php';

// Gifts & Souvenirs
require __DIR__ . '/gifts.api.php';

// Furniture & Interior
require __DIR__ . '/furniture.api.php';

// Electronics & Gadgets
require __DIR__ . '/electronics.api.php';

// Construction Materials
require __DIR__ . '/construction_materials.api.php';

// Toys & Kids
require __DIR__ . '/toys_kids.api.php';

// Music & Instruments
require __DIR__ . '/music.api.php';

// Analytics routes (heatmaps, comparisons, custom metrics)
require __DIR__ . '/analytics.api.php';

// Documentation (public)
Route::prefix('docs')->group(function () {
    Route::get('openapi.json', [\App\Http\Controllers\Api\OpenApiController::class, 'specification'])
        ->name('api.openapi.spec');
    Route::get('swagger', [\App\Http\Controllers\Api\OpenApiController::class, 'ui'])
        ->name('api.swagger.ui');
    Route::get('postman', [\App\Http\Controllers\Api\OpenApiController::class, 'postman'])
        ->name('api.postman.collection');
});

// ─── Channels (посты, подписки, реакции) ─────────────────────────────────────
require __DIR__ . '/channels.api.php';

}); // END: Global middleware group
