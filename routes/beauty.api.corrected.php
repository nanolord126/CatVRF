<?php declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\Beauty\AppointmentController;
use App\Http\Controllers\Api\V1\Beauty\SalonController;
use App\Http\Controllers\Api\V1\Beauty\MasterController;
use App\Http\Controllers\Api\V1\Beauty\ServiceController;
use App\Http\Controllers\Api\V1\Beauty\ReviewController;
use App\Http\Controllers\Api\V1\Beauty\ProductController;

/**
 * Beauty & Wellness API Routes v1 — CORRECTED MIDDLEWARE ARCHITECTURE
 *
 * Production 2026 CANON
 *
 * MIDDLEWARE ORDER (STRICT & IMMUTABLE):
 * 1. correlation-id        ✅ Инжекция X-Correlation-ID (ВСЕГДА ПЕРВЫМ)
 * 2. auth:sanctum          ✅ Валидация API token
 * 3. tenant                ✅ Tenant scoping
 * 4. idempotency-check     ✅ Детекция дубликатов (если нужно)
 * 5. b2c-b2b               ✅ Определение режима
 * 6. fraud-check           ✅ ML-скоринг фрода
 * 7. rate-limit            ✅ Ограничение по запросам
 * 8. age-verify            ✅ Проверка возраста (если нужно)
 *
 * Version: 2026.03.29
 */

// ===== PUBLIC ENDPOINTS (No Auth Required) =====
Route::middleware([
    'correlation-id',           // 1st - Always first
    'rate-limit:100,1',         // Light rate limit for public
])->group(function () {
    // List salons (with filters, geo)
    Route::get('/salons', [SalonController::class, 'index'])
        ->name('api.beauty.salons.index');

    // Get salon details
    Route::get('/salons/{id}', [SalonController::class, 'show'])
        ->name('api.beauty.salons.show');

    // Get salon availability
    Route::get('/salons/{id}/availability', [SalonController::class, 'availability'])
        ->name('api.beauty.salons.availability');

    // List masters (with filters, specialization)
    Route::get('/masters', [MasterController::class, 'index'])
        ->name('api.beauty.masters.index');

    // Get master profile
    Route::get('/masters/{id}', [MasterController::class, 'show'])
        ->name('api.beauty.masters.show');

    // Get master portfolio
    Route::get('/masters/{id}/portfolio', [MasterController::class, 'portfolio'])
        ->name('api.beauty.masters.portfolio');

    // Get master schedule
    Route::get('/masters/{id}/schedule', [MasterController::class, 'schedule'])
        ->name('api.beauty.masters.schedule');

    // List services
    Route::get('/services', [ServiceController::class, 'index'])
        ->name('api.beauty.services.index');

    // Get service details
    Route::get('/services/{id}', [ServiceController::class, 'show'])
        ->name('api.beauty.services.show');
});

// ===== AUTHENTICATED ENDPOINTS =====
Route::middleware([
    'correlation-id',       // 1st - Inject X-Correlation-ID
    'auth:sanctum',         // 2nd - Validate API token
    'tenant',               // 3rd - Tenant scoping
    'b2c-b2b',              // 4th - B2C/B2B mode determination
    'rate-limit',           // 5th - Per-endpoint throttling
    'fraud-check',          // 6th - ML fraud scoring (optional for appointments)
    'age-verify',           // 7th - Age verification (if needed)
])->group(function () {
    // ===== APPOINTMENTS =====
    Route::prefix('appointments')->group(function () {
        // Book appointment (CREATE)
        Route::post('/', [AppointmentController::class, 'store'])
            ->name('api.beauty.appointments.store')
            ->middleware('throttle:10,1');  // 10 requests/min max

        // View appointment details
        Route::get('/{id}', [AppointmentController::class, 'show'])
            ->name('api.beauty.appointments.show');

        // Cancel appointment
        Route::post('/{id}/cancel', [AppointmentController::class, 'cancel'])
            ->name('api.beauty.appointments.cancel')
            ->middleware('throttle:5,1');  // 5 cancellations/min max

        // Confirm appointment (owner only)
        Route::post('/{id}/confirm', [AppointmentController::class, 'confirm'])
            ->name('api.beauty.appointments.confirm')
            ->middleware('throttle:10,1');

        // Reschedule appointment
        Route::post('/{id}/reschedule', [AppointmentController::class, 'reschedule'])
            ->name('api.beauty.appointments.reschedule')
            ->middleware('throttle:5,1');

        // List user's appointments
        Route::get('/', [AppointmentController::class, 'index'])
            ->name('api.beauty.appointments.index');
    });

    // ===== REVIEWS =====
    Route::prefix('reviews')->group(function () {
        // Submit review after service
        Route::post('/', [ReviewController::class, 'store'])
            ->name('api.beauty.reviews.store')
            ->middleware('throttle:5,1');  // 5 reviews/min

        // Update own review
        Route::put('/{id}', [ReviewController::class, 'update'])
            ->name('api.beauty.reviews.update')
            ->middleware('throttle:5,1');

        // Delete own review
        Route::delete('/{id}', [ReviewController::class, 'destroy'])
            ->name('api.beauty.reviews.destroy')
            ->middleware('throttle:10,1');
    });

    // ===== WISHLIST =====
    Route::prefix('wishlist')->group(function () {
        // Add to wishlist
        Route::post('/add/{type}/{id}', [\App\Http\Controllers\Api\V1\Beauty\WishlistController::class, 'add'])
            ->name('api.beauty.wishlist.add')
            ->middleware('throttle:50,1');

        // Remove from wishlist
        Route::post('/remove/{type}/{id}', [\App\Http\Controllers\Api\V1\Beauty\WishlistController::class, 'remove'])
            ->name('api.beauty.wishlist.remove');

        // List wishlist
        Route::get('/', [\App\Http\Controllers\Api\V1\Beauty\WishlistController::class, 'index'])
            ->name('api.beauty.wishlist.index');
    });
});

// ===== BUSINESS OWNER ENDPOINTS =====
Route::middleware([
    'correlation-id',
    'auth:sanctum',
    'tenant',
    'b2c-b2b',
    'rate-limit:50,1',      // Moderate rate limit for management
    'fraud-check',
])->can('manage-beauty-business')->group(function () {
    // ===== SALON MANAGEMENT =====
    Route::apiResource('salons', SalonController::class)
        ->except('index', 'show');

    // ===== MASTER MANAGEMENT =====
    Route::apiResource('masters', MasterController::class)
        ->except('index', 'show');

    // ===== SERVICE MANAGEMENT =====
    Route::apiResource('services', ServiceController::class)
        ->except('index', 'show');

    // ===== PRODUCT MANAGEMENT =====
    Route::apiResource('products', ProductController::class)
        ->except('index', 'show');

    // ===== CONSUMABLES MANAGEMENT =====
    Route::apiResource('consumables', \App\Http\Controllers\Api\V1\Beauty\ConsumableController::class);

    // ===== ANALYTICS =====
    Route::get('/analytics/revenue', [\App\Http\Controllers\Api\V1\Beauty\AnalyticsController::class, 'revenue'])
        ->name('api.beauty.analytics.revenue');

    Route::get('/analytics/appointments', [\App\Http\Controllers\Api\V1\Beauty\AnalyticsController::class, 'appointments'])
        ->name('api.beauty.analytics.appointments');

    Route::get('/analytics/masters', [\App\Http\Controllers\Api\V1\Beauty\AnalyticsController::class, 'masters'])
        ->name('api.beauty.analytics.masters');
});
