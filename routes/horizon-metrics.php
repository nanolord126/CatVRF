<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use App\Services\Monitoring\HorizonPrometheusExporter;
use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| Horizon Metrics Routes
|--------------------------------------------------------------------------
|
| Routes for exporting Horizon queue metrics for Prometheus scraping
|
*/

Route::middleware(['web', 'auth', 'can:access-horizon'])->group(function () {
    Route::get('/metrics/horizon', function (HorizonPrometheusExporter $exporter) {
        return response($exporter->exportForHttp())
            ->header('Content-Type', 'text/plain; version=0.0.4');
    })->name('metrics.horizon');
});

// Public metrics endpoint for Prometheus scraping (protected by API token)
Route::get('/api/v1/metrics/horizon', function (Request $request, HorizonPrometheusExporter $exporter) {
    // Validate API token for Prometheus scraping
    $token = $request->header('X-Prometheus-Token');
    $expectedToken = config('monitoring.prometheus_token');

    if ($token !== $expectedToken) {
        return response()->json(['error' => 'Unauthorized'], 401);
    }

    return response($exporter->exportForHttp())
        ->header('Content-Type', 'text/plain; version=0.0.4');
})->name('api.metrics.horizon');
