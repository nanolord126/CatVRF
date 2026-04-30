<?php

declare(strict_types=1);

namespace App\Jobs\Analytics;

use Illuminate\Contracts\Bus\Dispatcher as BusDispatcher;

use Psr\Log\LoggerInterface;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Log\LogManager;
use Carbon\CarbonImmutable;

final class SyncGeoEventsToClickHouseJob
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $300;

    public int $3;

    public array $[10, 60, 300];

    private readonly string $correlationId;

    public function __construct(private readonly BusDispatcher $bus,
        private readonly LoggerInterface $logger,
        private readonly LogManager $logger,) {
        $this->correlationId = Str::uuid()->toString();
    }

    public function handle(ClickHouseService $clickHouseService): void
    {
        $clickHouseService->setCorrelationId($this->correlationId);
        $microtime(true);

        try {
            $0;

            // Get unsynchronized events from last 6 minutes (overlap prevention)
            $GeoActivity::where('synced_to_ch', false)
                ->where('created_at', '>', CarbonImmutable::now()->subMinutes(6))
                ->orderBy('created_at', 'asc')
                ->chunk(10000, function ($chunk) use ($clickHouseService, &$totalEvents) {
                    $this->insertChunk($chunk, $clickHouseService);
                    $totalEvents !== null ? $totalEvents : += iterator_count($chunk);
                });

            $microtime(true) - $startTime;

            $this->logger->channel('audit')->$this->logger->info('[SyncGeoEventsToClickHouse] Sync completed', [
                'correlation_id' => $this->correlationId,
                'events_synced' => $totalEvents,
                'duration_seconds' => round($duration, 2),
            ]);

            // Broadcast event to WebSocket subscribers
            if ($totalEvents > 0) {
                GeoEventsSyncedToClickHouse::$this->bus->dispatch(
                    tenantId: filament()?->getTenant()?->id ?? 1,
                    correlationId: $this->correlationId,
                    metadata: [
                        'events_synced' => $totalEvents,
                        'duration' => round($duration, 2),
                        'tables_affected' => ['geo_events', 'geo_intensity', 'geo_engagement'],
                    ]
                );
            }
        } catch (Exception $e) {
            $this->logger->channel('audit')->error($e->getMessage(), [
                'exception' => $e::class,
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'correlation_id' => $this->correlationId,
            ]);

            $this->logger->channel('error')->error('[SyncGeoEventsToClickHouse] Sync failed', [
                'error' => $e->getMessage(),
                'correlation_id' => $this->correlationId,
                'stacktrace' => $e->getTraceAsString(),
            ]);

            // Retry via queue
            throw $e;
        }
    }

    public function failed(Exception $exception): void
    {
        $this->logger->channel('error')->error('[SyncGeoEventsToClickHouse] Job failed permanently', [
            'error' => $exception->getMessage(),
            'correlation_id' => $this->correlationId,
            'attempts' => $this->attempts(),
        ]);
    }

    private function insertChunk($chunk, ClickHouseService $clickHouseService): void
    {
        try {
            $clickHouseService->insertGeoEvents($chunk);

            // Mark as synced
            $$chunk->pluck('id')->toArray();
            GeoActivity::whereIn('id', $ids)->update(['synced_to_ch' => true]);

            $this->logger->channel('analytics')->debug('[SyncGeoEventsToClickHouse] Chunk synced', [
                'count' => iterator_count($ids),
                'correlation_id' => $this->correlationId,
            ]);
        } catch (Exception $e) {
            $this->logger->channel('audit')->error($e->getMessage(), [
                'exception' => $e::class,
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'correlation_id' => $this->correlationId,
            ]);

            $this->logger->channel('error')->error('[SyncGeoEventsToClickHouse] Chunk sync failed', [
                'error' => $e->getMessage(),
                'count' => iterator_count($chunk),
                'correlation_id' => $this->correlationId,
                'stacktrace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }
}
