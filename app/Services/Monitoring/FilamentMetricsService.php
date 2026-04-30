<?php

declare(strict_types=1);

namespace App\Services\Monitoring;

use Illuminate\Contracts\Auth\Factory as AuthFactory;
use Prometheus\CollectorRegistry;
use Prometheus\Counter;
use Prometheus\Gauge;
use Prometheus\RenderTextFormat;

/**
 * Filament Metrics Service
 *
 * Collects and exports Prometheus metrics for Filament admin panels:
 * - Active sessions
 * - Resource view counts
 * - Action execution times
 * - Error rates
 * - Performance metrics
 */
final readonly class FilamentMetricsService
{
    public function __construct(
        private readonly AuthFactory $auth,
        private readonly CollectorRegistry $registry,
    ) {}

    /**
     * Increment resource view counter
     */
    public function incrementResourceView(string $resource, string $panel = 'tenant'): void
    {
        $counter = $this->registry->getOrRegisterCounter(
            namespace: 'filament',
            name: 'resource_views_total',
            help: 'Total number of resource views in Filament',
            labels: ['resource', 'panel', 'tenant_id']
        );

        $counter->inc([
            $resource,
            $panel,
            (string) ($this->auth->guard()->user()?->tenant_id ?? 'none'),
        ]);
    }

    /**
     * Record action execution time
     */
    public function recordActionExecution(string $action, float $duration, string $status = 'success', string $panel = 'tenant'): void
    {
        $histogram = $this->registry->getOrRegisterHistogram(
            namespace: 'filament',
            name: 'action_execution_duration_seconds',
            help: 'Duration of Filament action executions',
            labels: ['action', 'status', 'panel'],
            buckets: [0.1, 0.5, 1.0, 2.0, 5.0, 10.0, 30.0, 60.0]
        );

        $histogram->observe($duration, [
            $action,
            $status,
            $panel,
        ]);
    }

    /**
     * Increment error counter
     */
    public function incrementError(string $errorType, string $panel = 'tenant'): void
    {
        $counter = $this->registry->getOrRegisterCounter(
            namespace: 'filament',
            name: 'errors_total',
            help: 'Total number of errors in Filament',
            labels: ['error_type', 'panel', 'tenant_id']
        );

        $counter->inc([
            $errorType,
            $panel,
            (string) ($this->auth->guard()->user()?->tenant_id ?? 'none'),
        ]);
    }

    /**
     * Set active sessions gauge
     */
    public function setActiveSessions(int $count, string $panel = 'tenant'): void
    {
        $gauge = $this->registry->getOrRegisterGauge(
            namespace: 'filament',
            name: 'active_sessions',
            help: 'Number of active Filament sessions',
            labels: ['panel']
        );

        $gauge->set($count, [$panel]);
    }

    /**
     * Record query time
     */
    public function recordQueryTime(string $resource, float $duration, string $panel = 'tenant'): void
    {
        $histogram = $this->registry->getOrRegisterHistogram(
            namespace: 'filament',
            name: 'query_duration_seconds',
            help: 'Duration of database queries in Filament',
            labels: ['resource', 'panel'],
            buckets: [0.01, 0.05, 0.1, 0.5, 1.0, 2.0, 5.0]
        );

        $histogram->observe($duration, [$resource, $panel]);
    }

    /**
     * Record cache hit/miss
     */
    public function recordCacheAccess(string $cacheType, bool $hit, string $panel = 'tenant'): void
    {
        $counter = $this->registry->getOrRegisterCounter(
            namespace: 'filament',
            name: 'cache_access_total',
            help: 'Total cache accesses in Filament',
            labels: ['cache_type', 'result', 'panel']
        );

        $counter->inc([
            $cacheType,
            $hit ? 'hit' : 'miss',
            $panel,
        ]);
    }

    /**
     * Record widget render time
     */
    public function recordWidgetRender(string $widget, float $duration, string $panel = 'tenant'): void
    {
        $histogram = $this->registry->getOrRegisterHistogram(
            namespace: 'filament',
            name: 'widget_render_duration_seconds',
            help: 'Duration of widget renders in Filament',
            labels: ['widget', 'panel'],
            buckets: [0.1, 0.5, 1.0, 2.0, 5.0]
        );

        $histogram->observe($duration, [$widget, $panel]);
    }

    /**
     * Get all metrics in Prometheus format
     */
    public function getMetrics(): string
    {
        $renderer = new RenderTextFormat();

        return $renderer->render($this->registry->getMetricFamilySamples());
    }
}
