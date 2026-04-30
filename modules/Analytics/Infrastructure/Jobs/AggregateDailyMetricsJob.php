<?php

declare(strict_types=1);

namespace Modules\Analytics\Infrastructure\Jobs;

use App\Traits\WithAuditLogging;
use App\Services\AuditService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Carbon\CarbonImmutable;
use Illuminate\Cache\CacheManager;
use Illuminate\Database\DatabaseManager;

/**
 * Aggregate Daily Metrics Job
 *
 * Async job for aggregating daily analytics metrics from raw events.
 * Runs asynchronously to avoid blocking main request flow.
 * Follows production pattern: batch processing, cache invalidation.
 */
final class AggregateDailyMetricsJob implements ShouldQueue
{
    use Queueable;
    use Dispatchable;
    use InteractsWithQueue;
    use SerializesModels;
    use WithAuditLogging;

    public int $tries = 3;
    public int $timeout = 300;

    public function __construct(
        public readonly int $tenantId,
        public readonly ?CarbonImmutable $date = null,
    ) {
        $this->date ??= CarbonImmutable::now()->subDay();
    }

    public function handle(
        DatabaseManager $db,
        CacheManager $cache,
        AuditService $auditService,
    ): void {
        try {
            $date = $this->date->toDateString();

            // Aggregate events into daily metrics
            $metrics = $db->table('analytics_events')
                ->where('tenant_id', $this->tenantId)
                ->whereDate('occurred_at', $date)
                ->selectRaw('
                    COUNT(*) as events_count,
                    COUNT(DISTINCT user_id) as unique_users,
                    SUM(monetary_value) as total_monetary_value,
                    COUNT(CASE WHEN event_type LIKE "%order%" THEN 1 END) as orders_count
                ')
                ->first();

            // Upsert daily metrics
            $db->table('analytics_daily_metrics')
                ->updateOrInsert(
                    [
                        'tenant_id' => $this->tenantId,
                        'date' => $date,
                    ],
                    [
                        'sessions' => $metrics->events_count ?? 0,
                        'users_active' => $metrics->unique_users ?? 0,
                        'gmv' => (float) ($metrics->total_monetary_value ?? 0),
                        'orders_count' => $metrics->orders_count ?? 0,
                        'orders_revenue' => (float) ($metrics->total_monetary_value ?? 0),
                        'orders_aov' => $metrics->orders_count > 0 
                            ? (float) ($metrics->total_monetary_value / $metrics->orders_count) 
                            : 0,
                        'updated_at' => CarbonImmutable::now(),
                    ]
                );

            // Invalidate cache for affected tenant
            $cache->tags(["analytics:{$this->tenantId}"])->flush();

            $this->logAction(
                action: 'daily_metrics_aggregated',
                entityType: 'DailyMetrics',
                entityId: null,
                context: [
                    'tenant_id' => $this->tenantId,
                    'date' => $date,
                    'events_count' => $metrics->events_count ?? 0,
                    'unique_users' => $metrics->unique_users ?? 0,
                    'total_monetary_value' => $metrics->total_monetary_value ?? 0,
                ],
                userId: null,
                tenantId: $this->tenantId
            );
        } catch (\Exception $e) {
            $this->logError(
                operation: 'daily_metrics_aggregation',
                exception: $e,
                context: [
                    'tenant_id' => $this->tenantId,
                    'date' => $this->date->toDateString(),
                ]
            );

            $this->fail($e);
        }
    }

    public function failed(\Throwable $exception): void
    {
        $this->logError(
            operation: 'daily_metrics_aggregation_failed',
            exception: $exception instanceof \Exception ? $exception : new \Exception($exception->getMessage()),
            context: [
                'tenant_id' => $this->tenantId,
                'date' => $this->date->toDateString(),
            ]
        );
    }
}
