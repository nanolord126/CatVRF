<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Analytics\Infrastructure\Http\Controllers\AnalyticsApiController;
use Modules\Analytics\Infrastructure\Http\Controllers\SellerAnalyticsExportController;

/*
|--------------------------------------------------------------------------
| Analytics API Routes
|--------------------------------------------------------------------------
|
| API endpoints for external BI systems (Metabase, Power BI, Looker Studio).
| All routes require X-Tenant-ID header for multi-tenancy.
|
*/

Route::middleware(['auth:sanctum', 'throttle:api'])->group(function () {
    // Aggregated metrics
    Route::get('/analytics/metrics', [AnalyticsApiController::class, 'getMetrics']);
    
    // Time-series data
    Route::get('/analytics/timeseries', [AnalyticsApiController::class, 'getTimeSeries']);
    
    // Top items
    Route::get('/analytics/top-items', [AnalyticsApiController::class, 'getTopItems']);
    
    // Funnel analysis
    Route::get('/analytics/funnels', [AnalyticsApiController::class, 'getFunnel']);
    
    // Retention cohorts
    Route::get('/analytics/retention', [AnalyticsApiController::class, 'getRetentionCohorts']);
    
    // Export
    Route::get('/analytics/export', [AnalyticsApiController::class, 'export']);
    
    // Real-time metrics
    Route::get('/analytics/realtime', [AnalyticsApiController::class, 'getRealtimeMetrics']);
    
    // Seller-specific analytics
    Route::get('/analytics/sellers/{sellerId}', [AnalyticsApiController::class, 'getSellerAnalytics']);
    
    // Seller Analytics Dashboard (for mobile app)
    Route::get('/seller/analytics/dashboard', [AnalyticsApiController::class, 'getSellerDashboard']);
    Route::get('/seller/analytics/products', [AnalyticsApiController::class, 'getSellerProductAnalytics']);
    Route::get('/seller/analytics/insights', [AnalyticsApiController::class, 'getSellerInsights']);
    
    // Track events (for external systems)
    Route::post('/analytics/track', [AnalyticsApiController::class, 'trackEvent']);
});

// Public webhook endpoint for event tracking (with rate limiting)
Route::middleware(['throttle:60,1'])->post('/analytics/webhook', [AnalyticsApiController::class, 'trackEvent']);
