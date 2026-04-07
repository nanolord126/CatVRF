<?php declare(strict_types=1);

namespace App\Jobs\Analytics;

use Illuminate\Log\LogManager;

final class SyncClickEventsToClickHouseJob
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

                // Get unsynchronized events from last 6 minutes
                ClickEvent::where('synced_to_ch', false)
                    ->where('created_at', '>', now()->subMinutes(6))
                    ->orderBy('created_at', 'asc')
                    ->chunk(10000, function ($chunk) use ($clickHouseService, &$totalEvents) {
                        $this->insertChunk($chunk, $clickHouseService);
                        $totalEvents += count($chunk);
                    });

                $duration = microtime(true) - $startTime;

                $this->logger->channel('audit')->info('[SyncClickEventsToClickHouse] Sync completed', [
                    'correlation_id' => $this->correlationId,
                    'events_synced' => $totalEvents,
                    'duration_seconds' => round($duration, 2),
                ]);

                // Broadcast event to WebSocket subscribers
                if ($totalEvents > 0) {
                    ClickEventsSyncedToClickHouse::dispatch(
                        tenantId: filament()?->getTenant()?->id ?? 1,
                        correlationId: $this->correlationId,
                        metadata: [
                            'events_synced' => $totalEvents,
                            'duration' => round($duration, 2),
                            'tables_affected' => ['click_events', 'click_metrics'],
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

                $this->logger->channel('error')->error('[SyncClickEventsToClickHouse] Sync failed', [
                    'error' => $e->getMessage(),
                    'correlation_id' => $this->correlationId,
                    'stacktrace' => $e->getTraceAsString(),
                ]);

                throw $e;
            }
        }

        private function insertChunk($chunk, ClickHouseService $clickHouseService): void
        {
            try {
                $clickHouseService->insertClickEvents($chunk);

                // Mark as synced
                $ids = $chunk->pluck('id')->toArray();
                ClickEvent::whereIn('id', $ids)->update(['synced_to_ch' => true]);

                $this->logger->channel('analytics')->debug('[SyncClickEventsToClickHouse] Chunk synced', [
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

                $this->logger->channel('error')->error('[SyncClickEventsToClickHouse] Chunk sync failed', [
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
            $this->logger->channel('error')->error('[SyncClickEventsToClickHouse] Job failed permanently', [
                'error' => $exception->getMessage(),
                'correlation_id' => $this->correlationId,
                'attempts' => $this->attempts(),
            ]);
        }
}
