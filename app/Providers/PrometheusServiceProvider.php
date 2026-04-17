<?php declare(strict_types=1);

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Spatie\Prometheus\Facades\Prometheus;

/**
 * PrometheusServiceProvider — Service Provider for Prometheus Metrics
 * 
 * Registers custom ML metrics for CatVRF using Spatie Prometheus facade.
 * Metrics are registered directly here since Spatie Laravel Prometheus
 * uses a different pattern than CollectorInterface.
 * 
 * Metrics registered:
 * - ML model metrics: version, AUC, shadow mode status
 * - Feature drift metrics: PSI, KS, JS divergence scores
 * - Quota metrics: usage ratios and limits
 * - Fraud ML metrics: inference latency, fraud scores, blocked count
 * 
 * @author CatVRF Team
 * @version 2026.04.17
 */
final class PrometheusServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Metrics are registered dynamically via PrometheusMetricsService
        // No static registration needed in this provider
    }
}
