<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\BigData\Presentation\Http\Controllers\CostController;

/*
|--------------------------------------------------------------------------
| BigData Cost Monitoring Routes
|--------------------------------------------------------------------------
|
| FinOps / Cost Monitoring API endpoints.
| Authenticated via middleware (except Prometheus metrics scrape).
|
*/

// Prometheus metrics scrape endpoint (no auth — secured at network level)
Route::get('metrics/bigdata-cost', [CostController::class, 'metrics']);

// Authenticated API routes
Route::middleware(['auth:sanctum', 'throttle:60,1'])->prefix('bigdata/cost')->group(function () {

    // Overview
    Route::get('snapshot', [CostController::class, 'snapshot']);
    Route::get('daily', [CostController::class, 'dailyBreakdown']);
    Route::get('timeseries', [CostController::class, 'timeSeries']);

    // Seller attribution
    Route::get('seller/{sellerId}', [CostController::class, 'sellerAttribution']);
    Route::get('top-sellers', [CostController::class, 'topSellers']);

    // Prediction
    Route::get('predict', [CostController::class, 'predict']);

    // Optimization
    Route::get('recommendations', [CostController::class, 'recommendations']);
    Route::post('auto-optimize', [CostController::class, 'autoOptimize']);

    // Deep dive
    Route::get('clickhouse', [CostController::class, 'clickhouseCost']);
    Route::get('kafka', [CostController::class, 'kafkaCost']);
    Route::get('spark-ml', [CostController::class, 'sparkMLCost']);

    // Unit economics
    Route::get('unit-economics', [CostController::class, 'unitEconomics']);

    // Anomalies
    Route::get('anomalies', [CostController::class, 'anomalies']);
    Route::post('detect-anomalies', [CostController::class, 'detectAnomalies']);
});
