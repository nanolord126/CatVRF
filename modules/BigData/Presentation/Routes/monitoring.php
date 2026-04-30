<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\BigData\Presentation\Http\Controllers\MetricsController;
use Modules\BigData\Presentation\Http\Controllers\MonitoringController;

/*
|--------------------------------------------------------------------------
| BigData Monitoring Routes
|--------------------------------------------------------------------------
|
| Routes for the BigData monitoring subsystem.
| Metrics endpoint is unauthenticated (for Prometheus scraping).
| Monitoring API requires authentication.
|
*/

// Prometheus metrics scrape endpoint (no auth — secured at network level)
Route::get('metrics/bigdata', MetricsController::class);

// Monitoring API (authenticated)
Route::prefix('api/bigdata/monitoring')->middleware(['auth:api', 'throttle:60,1'])->group(function () {
    Route::get('snapshot', [MonitoringController::class, 'snapshot']);
    Route::get('pipeline', [MonitoringController::class, 'pipeline']);
    Route::get('freshness', [MonitoringController::class, 'freshness']);
    Route::get('clv-drift', [MonitoringController::class, 'clvDrift']);
    Route::get('query-perf', [MonitoringController::class, 'queryPerformance']);
    Route::get('alerts', [MonitoringController::class, 'alerts']);
    Route::post('self-heal', [MonitoringController::class, 'selfHeal']);
    Route::post('maintenance', [MonitoringController::class, 'maintenance']);
});
