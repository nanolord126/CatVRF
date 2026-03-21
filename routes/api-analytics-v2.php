<?php

declare(strict_types=1);

use App\Http\Controllers\Api\V2\Analytics\AnalyticsController;
use App\Http\Controllers\Api\V2\Analytics\ReportingController;
use App\Http\Controllers\Api\V2\Analytics\ExportController;
use Illuminate\Support\Facades\Route;

/**
 * Phase 7: Advanced Analytics Routes
 * 
 * Prefix: /api/v2
 * Middleware: auth:sanctum, rate-limit-analytics
 */

Route::middleware(['auth:sanctum', 'rate-limit-analytics'])->prefix('api/v2')->group(function () {
    // Analytics Endpoints
    Route::get('/analytics/metrics', [AnalyticsController::class, 'getMetrics']);
    Route::get('/analytics/kpis', [AnalyticsController::class, 'getKPIs']);
    Route::get('/analytics/forecast', [AnalyticsController::class, 'getForecast']);
    Route::get('/analytics/report', [AnalyticsController::class, 'getReport']);
    Route::post('/analytics/comparison', [AnalyticsController::class, 'compareMetrics']);

    // Reporting Endpoints
    Route::post('/reporting/schedule', [ReportingController::class, 'scheduleReport']);
    Route::get('/reporting/scheduled', [ReportingController::class, 'getScheduledReports']);
    Route::put('/reporting/{reportId}/schedule', [ReportingController::class, 'updateSchedule']);
    Route::delete('/reporting/{reportId}', [ReportingController::class, 'deleteSchedule']);
    Route::get('/reporting/generate', [ReportingController::class, 'generateReport']);

    // Export & Segments Endpoints
    Route::post('/exports/create', [ExportController::class, 'createExport']);
    Route::get('/exports/history', [ExportController::class, 'getHistory']);
    Route::get('/segments', [ExportController::class, 'getSegments']);
    Route::get('/segments/compare', [ExportController::class, 'compareSegments']);
});
