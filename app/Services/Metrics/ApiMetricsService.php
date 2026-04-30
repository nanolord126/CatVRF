<?php

declare(strict_types=1);

namespace App\Services\Metrics;

use Illuminate\Log\LogManager;
use Prometheus\CollectorRegistry;
use Prometheus\Histogram;
use Prometheus\Counter;
use Prometheus\Gauge;

final class ApiMetricsService
{
    private readonly Counter $requestsTotal;

    private readonly Histogram $requestDuration;

    private readonly Counter $errorsTotal;

    private readonly Gauge $activeConnections;

    public function __construct(
        private readonly CollectorRegistry $registry,
        private readonly LogManager $log,
    ) {
        $this->initMetrics();
    }

    /**
     * Record API request
     */
    public function recordRequest(
        string $method,
        string $endpoint,
        int $status,
        ?string $vertical = null,
        string $version = 'v1'
    ): void {
        $this->requestsTotal->incBy(
            1,
            [$method, $endpoint, (string) $status, $vertical ?? 'unknown', $version]
        );
    }

    /**
     * Record API request duration
     */
    public function recordDuration(
        float $duration,
        string $method,
        string $endpoint,
        ?string $vertical = null,
        string $version = 'v1'
    ): void {
        $this->requestDuration->observe(
            $duration,
            [$method, $endpoint, $vertical ?? 'unknown', $version]
        );
    }

    /**
     * Record API error
     */
    public function recordError(
        string $method,
        string $endpoint,
        int $status,
        string $errorType,
        ?string $vertical = null
    ): void {
        $this->errorsTotal->incBy(
            1,
            [$method, $endpoint, (string) $status, $errorType, $vertical ?? 'unknown']
        );

        $this->log->warning('API error recorded', [
            'method' => $method,
            'endpoint' => $endpoint,
            'status' => $status,
            'error_type' => $errorType,
            'vertical' => $vertical,
        ]);
    }

    /**
     * Increment active connections
     */
    public function incrementActiveConnections(?string $vertical = null): void
    {
        $this->activeConnections->incBy(1, [$vertical ?? 'unknown']);
    }

    /**
     * Decrement active connections
     */
    public function decrementActiveConnections(?string $vertical = null): void
    {
        $this->activeConnections->decBy(1, [$vertical ?? 'unknown']);
    }

    /**
     * Get metrics for export
     */
    public function getMetrics(): array
    {
        return $this->registry->getMetricFamilySamples();
    }

    private function initMetrics(): void
    {
        $this->requestsTotal = $this->registry->getOrRegisterCounter(
            'catvrf',
            'api_requests_total',
            'Total API requests',
            ['method', 'endpoint', 'status', 'vertical', 'version']
        );

        $this->requestDuration = $this->registry->getOrRegisterHistogram(
            'catvrf',
            'api_request_duration_seconds',
            'API request duration in seconds',
            ['method', 'endpoint', 'vertical', 'version'],
            [0.005, 0.01, 0.025, 0.05, 0.1, 0.25, 0.5, 1, 2.5, 5, 10]
        );

        $this->errorsTotal = $this->registry->getOrRegisterCounter(
            'catvrf',
            'api_errors_total',
            'Total API errors',
            ['method', 'endpoint', 'status', 'error_type', 'vertical']
        );

        $this->activeConnections = $this->registry->getOrRegisterGauge(
            'catvrf',
            'api_active_connections',
            'Active API connections by vertical',
            ['vertical']
        );
    }
}
