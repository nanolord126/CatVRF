<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use App\Services\ML\FeatureDriftMetricsService;

/*
|--------------------------------------------------------------------------
| ML Metrics Routes
|--------------------------------------------------------------------------
|
| Endpoints for ML model monitoring and drift detection metrics.
| Used by Prometheus/OpenTelemetry for scraping.
|
*/

Route::middleware(['throttle:60,1'])->group(function () {
    // Prometheus metrics endpoint for drift detection
    Route::get('/metrics/fraud/drift', function (FeatureDriftMetricsService $metricsService) {
        return response($metricsService->exportPrometheusMetrics(), 200)
            ->header('Content-Type', 'text/plain; version=0.0.4');
    })->name('metrics.fraud.drift');

    // Grafana dashboard JSON endpoint
    Route::get('/api/v1/ml/fraud/drift/metrics', function (FeatureDriftMetricsService $metricsService) {
        return response()->json($metricsService->getGrafanaMetrics());
    })->name('api.ml.fraud.drift.metrics');
});
