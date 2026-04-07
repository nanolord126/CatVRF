<?php declare(strict_types=1);

namespace App\Jobs\Analytics;

use Illuminate\Log\LogManager;

final class SyncGeoEventsToClickHouseJob
{

    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

        private string $correlationId;
        public int $timeout = 300;
        public int $tries = 3;
        public array $backoff = [10, 60, 300];

        public function __construct(
        private readonly LogManager $logger,
    )
        {
            $this->correlationId = Str::uuid()->toString();
        }

        public function handle(ClickHouseService $clickHouseService): void
        {
            $clickHouseService->setCorrelationId($this->correlationId);
            $startTime = microtime(true);

            try {
                $totalEvents = 0;

                // Get unsynchronized events from last 6 minutes (overlap prevention)
                $events = GeoActivity::where('synced_to_ch', false)
                    ->where('created_at', '>', now()->subMinutes(6))
                    ->orderBy('created_at', 'asc')
                    ->chunk(10000, function ($chunk) use ($clickHouseService, &$totalEvents) {
                        $this->insertChunk($chunk, $clickHouseService);
                        $totalEvents += count($chunk);
                    });

                $duration = microtime(true) - $startTime;

                $this->logger->channel('audit')->info('[SyncGeoEventsToClickHouse] Sync completed', [
                    'correlation_id' => $this->correlationId,
                    'events_synced' => $totalEvents,
                    'duration_seconds' => round($duration, 2),
                ]);

                // Broadcast event to WebSocket subscribers
                if ($totalEvents > 0) {
                    GeoEventsSyncedToClickHouse::dispatch(
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
                \Illuminate\Support\Facades\Log::channel('audit')->error($e->getMessage(), [
                    'exception' => $e::class,
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'correlation_id' => request()->header('X-Correlation-ID'),
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

        private function insertChunk($chunk, ClickHouseService $clickHouseService): void
        {
            try {
                $clickHouseService->insertGeoEvents($chunk);

                // Mark as synced
                $ids = $chunk->pluck('id')->toArray();
                GeoActivity::whereIn('id', $ids)->update(['synced_to_ch' => true]);

                $this->logger->channel('analytics')->debug('[SyncGeoEventsToClickHouse] Chunk synced', [
                    'count' => count($ids),
                    'correlation_id' => $this->correlationId,
                ]);
            } catch (Exception $e) {
                \Illuminate\Support\Facades\Log::channel('audit')->error($e->getMessage(), [
                    'exception' => $e::class,
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'correlation_id' => request()->header('X-Correlation-ID'),
                ]);

                $this->logger->channel('error')->error('[SyncGeoEventsToClickHouse] Chunk sync failed', [
                    'error' => $e->getMessage(),
                    'count' => count($chunk),
                    'correlation_id' => $this->correlationId,
                    'stacktrace' => $e->getTraceAsString(),
                ]);

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
}
