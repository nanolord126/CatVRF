<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

// WMS Routes
require __DIR__.'/api/wms.php';

// Payment Compliance Routes
require __DIR__.'/api/payment_compliance.php';
use Illuminate\Http\Request;
use App\Domains\Advertising\Presentation\Http\Controllers\AdController;
use App\Domains\Hotels\Http\Controllers\HotelController;
use App\Domains\Taxi\Http\Controllers\TaxiCardController;
use App\Domains\Taxi\Http\Controllers\TaxiOrderController;
use App\Http\Controllers\Api\OpenApiController;
use App\Http\Controllers\Api\PIIController;
use App\Http\Controllers\Api\UniversalOrderController;
use App\Http\Controllers\Api\V1\B2B\Taxi\DriverRideController;
use App\Http\Controllers\Api\V1\B2C\Taxi\TrackRideController;
use App\Http\Controllers\Api\V1\PaymentController;
use App\Http\Controllers\Api\V1\PromoController;
use App\Http\Controllers\Api\V1\SearchController;
use App\Http\Controllers\Api\V1\Wallet\WalletController;
use App\Http\Controllers\EventMetricsController;
use App\Http\Controllers\Internal\WebhookController;
use App\Http\Middleware\IpWhitelistMiddleware;

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

// ===== STRESS TEST ENDPOINT (No Middleware - Public) =====
Route::get('/stress-test', function () {
    return response()->json([
        'success' => true,
        'message' => 'Stress test endpoint',
        'timestamp' => now(),
    ]);
})->name('api.stress-test');

// ===== PROMETHEUS METRICS ENDPOINT (No Middleware - Internal) =====
Route::get('/metrics', EventMetricsController::class)
    ->name('api.metrics');

Route::get('/health/events', [EventMetricsController::class, 'health'])
    ->name('api.health.events');

// ===== GLOBAL MIDDLEWARE (all routes) =====
Route::group([], function () {

    // ===== API V1 PRODUCTION ROUTES =====
    // All routes defined in routes/api-v1.php with tenant + auth middleware
    require base_path('routes/api-v1.php');

    // ===== AUTHENTICATION ROUTES =====
    require base_path('routes/api-v1-auth.php');

    // ===== SECURITY & ACCOUNT PROTECTION ROUTES =====
    require base_path('routes/security.api.php');

    // ===== VOICE BIOMETRICS ROUTES =====
    require base_path('routes/api/voice_biometrics.php');

    // ===== CONSENT MANAGEMENT ROUTES =====
    require base_path('routes/api/consent_management.php');

    // ===== AML SCREENING ROUTES =====
    require base_path('routes/api/aml.php');

    // ===== CONTINUOUS AUTHENTICATION ROUTES =====
    require base_path('routes/api/continuous-auth.php');

    // ===== FOUR-EYES APPROVAL ROUTES =====
    require base_path('routes/api/approvals.php');

    // ===== BEAUTY VERTICAL =====
    require base_path('routes/api/beauty.php');

    // ===== EDUCATION VERTICAL =====
    require base_path('routes/education.api.php');

    // ===== TAXI B2C (passenger) =====
    Route::prefix('api/v1/b2c/taxi')
        ->middleware(['auth:sanctum'])
        ->group(function () {
            // Route::post('rides/request', \App\Http\Controllers\Api\V1\B2C\Taxi\RideController::class)
            // ->name('b2c.taxi.rides.request');
            Route::get('rides/{rideId}', TrackRideController::class)
                ->name('b2c.taxi.rides.track');
        });

    // ===== HOTELS B2C (guest) =====
    Route::prefix('api/v1/b2c/hotels')
        ->middleware(['auth:sanctum'])
        ->group(function () {
            Route::get('search', [HotelController::class, 'search'])
                ->name('b2c.hotels.search');
            Route::post('book', [HotelController::class, 'book'])
                ->name('b2c.hotels.book');
        });

    // ===== TAXI B2B (driver app) =====
    Route::prefix('api/v1/b2b/taxi')
        ->middleware(['auth:sanctum'])
        ->group(function () {
            Route::post('rides/{rideId}/accept', [DriverRideController::class, 'accept'])
                ->name('b2b.taxi.rides.accept');
            Route::post('rides/{rideId}/start', [DriverRideController::class, 'start'])
                ->name('b2b.taxi.rides.start');
            Route::post('rides/{rideId}/finish', [DriverRideController::class, 'finish'])
                ->name('b2b.taxi.rides.finish');
        });

    // ===== LEGACY API V1 - Authenticated (Backward Compatibility) =====
    Route::prefix('v1')
        ->middleware('auth:sanctum')
        ->group(function () {

            // Payment routes
            Route::prefix('payments')->group(function () {
                Route::post('/', [PaymentController::class, 'store'])
                    ->name('v1.payments.store');
                Route::get('{payment}', [PaymentController::class, 'show'])
                    ->name('v1.payments.show');
                Route::post('{payment}/refund', [PaymentController::class, 'refund'])
                    ->name('v1.payments.refund');
            });

            // Wallet routes
            Route::prefix('wallets')->group(function () {
                Route::get('/', [WalletController::class, 'index'])
                    ->name('v1.wallets.index');
                Route::get('{wallet}', [WalletController::class, 'show'])
                    ->name('v1.wallets.show');
                Route::post('{wallet}/deposit', [WalletController::class, 'deposit'])
                    ->name('v1.wallets.deposit');
                Route::post('{wallet}/withdraw', [WalletController::class, 'withdraw'])
                    ->name('v1.wallets.withdraw')
                    ->middleware('VpnProtectionMiddleware');
            });

            // Promo routes
            Route::prefix('promos')->group(function () {
                Route::post('apply', [PromoController::class, 'apply'])
                    ->name('v1.promos.apply');
            });

            // Search routes
            Route::prefix('search')->group(function () {
                Route::get('/', [SearchController::class, 'index'])
                    ->name('v1.search.index');
                Route::get('suggestions', [SearchController::class, 'suggestions'])
                    ->name('v1.search.suggestions');
            });

            // Taxi card routes
            Route::prefix('taxi')->group(function () {
                Route::get('drivers/{driver}', [TaxiCardController::class, 'getDriverCard'])
                    ->name('v1.taxi.drivers.card');
                Route::get('vehicles/{vehicle}', [TaxiCardController::class, 'getVehicleCard'])
                    ->name('v1.taxi.vehicles.card');
                Route::get('tariffs', [TaxiCardController::class, 'getTariffs'])
                    ->name('v1.taxi.tariffs');
                Route::get('tariffs/{tariff}', [TaxiCardController::class, 'getTariff'])
                    ->name('v1.taxi.tariffs.show');
                Route::get('passengers/{passenger}', [TaxiCardController::class, 'getPassengerProfile'])
                    ->middleware('auth:sanctum')
                    ->name('v1.taxi.passengers.profile');
                Route::get('rides/{ride}', [TaxiCardController::class, 'getRideCard'])
                    ->name('v1.taxi.rides.card');
                Route::get('nearby-drivers', [TaxiCardController::class, 'getNearbyDrivers'])
                    ->name('v1.taxi.nearby-drivers');

                // Taxi order routes
                Route::post('orders', [TaxiOrderController::class, 'createOrder'])
                    ->middleware('auth:sanctum')
                    ->name('v1.taxi.orders.create');
                Route::get('orders/{ride}', [TaxiOrderController::class, 'getOrder'])
                    ->middleware('auth:sanctum')
                    ->name('v1.taxi.orders.show');
                Route::put('orders/{ride}', [TaxiOrderController::class, 'updateOrder'])
                    ->middleware('auth:sanctum')
                    ->name('v1.taxi.orders.update');
                Route::post('orders/{ride}/cancel', [TaxiOrderController::class, 'cancelOrder'])
                    ->middleware('auth:sanctum')
                    ->name('v1.taxi.orders.cancel');
                Route::post('orders/{ride}/rate', [TaxiOrderController::class, 'rateOrder'])
                    ->middleware('auth:sanctum')
                    ->name('v1.taxi.orders.rate');
                Route::get('orders', [TaxiOrderController::class, 'getUserOrders'])
                    ->middleware('auth:sanctum')
                    ->name('v1.taxi.orders.index');
                Route::post('estimate-price', [TaxiOrderController::class, 'estimatePrice'])
                    ->name('v1.taxi.estimate-price');
            });
        });

    // API v2 - Future version
    Route::prefix('v2')
        ->middleware('auth:sanctum')
        ->group(function () {
            Route::prefix('payments')->group(function () {
                Route::post('init', [App\Http\Controllers\Api\V2\PaymentController::class, 'init'])
                    ->name('v2.payments.init');
                Route::get('{payment}', [App\Http\Controllers\Api\V2\PaymentController::class, 'show'])
                    ->name('v2.payments.show');
            });

            // Search routes
            Route::prefix('search')->group(function () {
                Route::get('documents', [App\Http\Controllers\Api\V2\Search\SearchController::class, 'searchDocuments'])
                    ->name('v2.search.documents');
                Route::get('users', [App\Http\Controllers\Api\V2\Search\SearchController::class, 'searchUsers'])
                    ->name('v2.search.users');
                Route::get('history', [App\Http\Controllers\Api\V2\Search\SearchController::class, 'getHistory'])
                    ->name('v2.search.history');
                Route::delete('history', [App\Http\Controllers\Api\V2\Search\SearchController::class, 'clearHistory'])
                    ->name('v2.search.history.clear');
            });
        });

    // Webhook routes (IP whitelisted, no auth)
    Route::prefix('webhooks')
        ->middleware([IpWhitelistMiddleware::class])
        ->group(function () {
            Route::post('tinkoff', [WebhookController::class, 'handleTinkoff'])
                ->name('webhooks.tinkoff');
            Route::post('sber', [WebhookController::class, 'handleSber'])
                ->name('webhooks.sber');
            Route::post('sbp', [WebhookController::class, 'handleSbp'])
                ->name('webhooks.sbp');
        });

    // ─── B2B API Routes ───────────────────────────────────────────────────────
    require __DIR__.'/api/b2b.php';

    // ─── Verticals API Routes ─────────────────────────────────────────────────────
    // Beauty & Wellness
    require __DIR__.'/beauty.api.php';

    // Food & Delivery
    require __DIR__.'/food.api.php';

    // Hotels & Accommodation
    require __DIR__.'/hotels.api.php';

    // Auto & Taxi & Services
    require __DIR__.'/auto.api.php';

    // Real Estate
    require __DIR__.'/realestate.api.php';

    // Courses & Education
    require __DIR__.'/courses.api.php';

    // Medical & Healthcare
    require __DIR__.'/medical.api.php';

    // Archived Art Artistic Services (legacy vertical, tenant + auth)
    // TODO: ArtisticProjectController not found - controller needs to be created or routes removed
    // Route::prefix('archived/artistic-services')
    //     ->middleware(['auth:sanctum', 'tenant'])
    //     ->group(function (): void {
    //         Route::get('/', [\App\Domains\Archived\Art\ArtisticServices\Http\Controllers\ArtisticProjectController::class, 'index'])
    //             ->name('archived.artistic-services.index');
    //         Route::post('/', [\App\Domains\Archived\Art\ArtisticServices\Http\Controllers\ArtisticProjectController::class, 'store'])
    //             ->name('archived.artistic-services.store');
    //         Route::get('{projectId}', [\App\Domains\Archived\Art\ArtisticServices\Http\Controllers\ArtisticProjectController::class, 'show'])
    //             ->name('archived.artistic-services.show');
    //         Route::post('{projectId}/complete', [\App\Domains\Archived\Art\ArtisticServices\Http\Controllers\ArtisticProjectController::class, 'complete'])
    //             ->name('archived.artistic-services.complete');
    //         Route::post('{projectId}/cancel', [\App\Domains\Archived\Art\ArtisticServices\Http\Controllers\ArtisticProjectController::class, 'cancel'])
    //             ->name('archived.artistic-services.cancel');
    //     });

    // Pet Services & Clinics
    require __DIR__.'/pet.api.php';

    // Entertainment (Tickets, Events)
    require __DIR__.'/tickets.api.php';

    // Travel & Tourism
    require __DIR__.'/travel.api.php';

    // Sports & Fitness
    require __DIR__.'/sports.api.php';

    // Freelance & Services
    require __DIR__.'/freelance.api.php';

    // Photography & Video
    require __DIR__.'/photography.api.php';

    // Logistics & Courier
    require __DIR__.'/logistics.api.php';

    // Fresh Produce & Delivery
    require __DIR__.'/fresh_produce.api.php';

    // Grocery Delivery
    require __DIR__.'/grocery.api.php';

    // Pharmacy & Medical Supplies
    require __DIR__.'/pharmacy.api.php';

    // Healthy Food & Diet
    require __DIR__.'/healthy_food.api.php';

    // Confectionery & Bakery
    require __DIR__.'/confectionery.api.php';

    // Meat Shops
    require __DIR__.'/meat_shops.api.php';

    // Office Catering
    require __DIR__.'/office_catering.api.php';

    // Farm Direct
    require __DIR__.'/farm_direct.api.php';

    // Books & Literature
    require __DIR__.'/books.api.php';

    // Cosmetics & Perfume
    require __DIR__.'/cosmetics.api.php';

    // Jewelry
    require __DIR__.'/jewelry.api.php';

    // Gifts & Souvenirs
    require __DIR__.'/gifts.api.php';

    // Furniture & Interior
    require __DIR__.'/furniture.api.php';

    // Electronics & Gadgets
    require __DIR__.'/electronics.api.php';

    // Construction Materials
    require __DIR__.'/construction_materials.api.php';

    // Toys & Kids
    require __DIR__.'/toys_kids.api.php';

    // Music & Instruments
    require __DIR__.'/music.api.php';

    // ... existing code ...

    // Analytics routes (heatmaps, comparisons, custom metrics)
    require __DIR__.'/analytics.api.php';

    // Documentation (public)
    Route::prefix('docs')->group(function () {
        Route::get('openapi.json', [OpenApiController::class, 'specification'])
            ->name('api.openapi.spec');
        Route::get('swagger', [OpenApiController::class, 'ui'])
            ->name('api.swagger.ui');
        Route::get('postman', [OpenApiController::class, 'postman'])
            ->name('api.postman.collection');
    });

    // ─── Channels (посты, подписки, реакции) ─────────────────────────────────────
    require __DIR__.'/channels.api.php';

    // ===== ADDITIONAL VERTICALS =====
    require __DIR__.'/art.api.php';
    require __DIR__.'/fashion.api.php';
    require __DIR__.'/taxi.api.php';

    // ===== PRIORITY 1 VERTICALS (NEWLY ADDED) =====
    require __DIR__.'/advertising.api.php';
    require __DIR__.'/delivery.api.php';
    require __DIR__.'/event-planning.api.php';
    require __DIR__.'/wedding-planning.api.php';
    require __DIR__.'/car-rental.api.php';

    // ===== PRIORITY 2 VERTICALS (95% COVERAGE) =====
    require __DIR__.'/cleaning-services.api.php';
    require __DIR__.'/collectibles.api.php';
    require __DIR__.'/communication.api.php';
    require __DIR__.'/consulting.api.php';
    require __DIR__.'/content.api.php';
    require __DIR__.'/finances.api.php';
    require __DIR__.'/flowers.api.php';
    require __DIR__.'/gardening.api.php';
    require __DIR__.'/hobby-and-craft.api.php';
    require __DIR__.'/household-goods.api.php';
    require __DIR__.'/insurance.api.php';
    require __DIR__.'/inventory.api.php';
    require __DIR__.'/legal.api.php';
    require __DIR__.'/marketplace.api.php';
    require __DIR__.'/sports-nutrition.api.php';
    require __DIR__.'/staff.api.php';
    require __DIR__.'/vegan-products.api.php';
    require __DIR__.'/veterinary.api.php';

    // ===== PRIORITY 3 VERTICALS (100% COVERAGE) =====
    require __DIR__.'/party-supplies.api.php';

    // ===== OCTANE HEALTH MONITORING =====
    require __DIR__.'/octane.api.php';

    // ===== PASSKEY AUTHENTICATION (Passwordless WebAuthn) =====
    require __DIR__.'/api/passkey.php';

    // ===== SIEM DASHBOARD (Security Information and Event Management) =====
    require __DIR__.'/api/siem.php';

    // Include vertical-specific routes
    require __DIR__.'/api/supermarket-temperature.php';

    // Include shared tender routes
    require __DIR__.'/api/tender.php';

    // ===== SUPERMARKET BUYER FLOW (Cart, Checkout, Tracking, Recommendations) =====
    require __DIR__.'/api/supermarket-buyer.php';

    // ===== SUPERMARKET CRM FLOW (Customer Management, Analytics, Returns) =====
    require __DIR__.'/api/supermarket-crm.php';

    // ===== CRM TASKS & KPI FLOW (Task Management, Manager KPI, All Verticals) =====
    require __DIR__.'/api/crm-tasks.php';

    // ─── Universal Order API (B2C/B2B for all verticals) ─────────────────────────────
    Route::prefix('api/v1/orders')
        ->middleware(['auth:sanctum', 'order'])
        ->group(function () {
            Route::post('/', [UniversalOrderController::class, 'createOrder'])
                ->name('orders.create');
            Route::get('/{uuid}', [UniversalOrderController::class, 'getOrder'])
                ->name('orders.get');
            Route::get('/', [UniversalOrderController::class, 'listOrders'])
                ->name('orders.list');
        });

    // API route for showing advertisements
    Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
        return $request->user();
    });

    Route::middleware(['auth:sanctum', 'tenant'])->group(function () {
        Route::get('/ad', [AdController::class, 'show']);
        // TODO: AnalyticsController not found - need to specify full namespace or create controller
        // Route::post('/analytics/track', [AnalyticsController::class, 'track']);
    });

    // ===== PII COMPLIANCE ENDPOINTS (152-ФЗ) =====
    Route::prefix('api/v1/pii')
        ->middleware(['auth:sanctum'])
        ->group(function () {
            // Right to be forgotten
            Route::post('/deletion/request', [PIIController::class, 'requestDeletion'])
                ->name('pii.deletion.request');
            Route::get('/deletion/check/{user_id}', [PIIController::class, 'checkPIIExistence'])
                ->name('pii.deletion.check');
            Route::get('/deletion/report/{user_id}', [PIIController::class, 'generateDeletionReport'])
                ->name('pii.deletion.report');

            // Consent management
            Route::post('/consent', [PIIController::class, 'createConsent'])
                ->name('pii.consent.create');
            Route::post('/consent/{consent_id}/revoke', [PIIController::class, 'revokeConsent'])
                ->name('pii.consent.revoke');
            Route::get('/consents/{user_id}', [PIIController::class, 'checkConsents'])
                ->name('pii.consent.check');
            Route::get('/consents/{user_id}/history', [PIIController::class, 'getConsentHistory'])
                ->name('pii.consent.history');
            Route::post('/consents/check-required', [PIIController::class, 'checkRequiredConsents'])
                ->name('pii.consent.check-required');
        });

}); // END: Global middleware group
