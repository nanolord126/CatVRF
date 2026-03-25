<?php

declare(strict_types=1);

namespace App\Jobs\Analytics;

use App\Domains\Analytics\Services\ClickHouseService;
use App\Domains\Geo\Models\GeoActivity;
use App\Events\Analytics\GeoEventsSyncedToClickHouse;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Str;

final class SyncGeoEventsToClickHouseJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private string $correlationId;
    public int $timeout = 300;
    public int $tries = 3;
    public array $backoff = [10, 60, 300];

    public function __construct()
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

            $this->log->channel('audit')->info('[SyncGeoEventsToClickHouse] Sync completed', [
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
            $this->log->channel('error')->error('[SyncGeoEventsToClickHouse] Sync failed', [
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

            $this->log->channel('analytics')->debug('[SyncGeoEventsToClickHouse] Chunk synced', [
                'count' => count($ids),
                'correlation_id' => $this->correlationId,
            ]);
        } catch (Exception $e) {
            $this->log->channel('error')->error('[SyncGeoEventsToClickHouse] Chunk sync failed', [
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
        $this->log->channel('error')->error('[SyncGeoEventsToClickHouse] Job failed permanently', [
            'error' => $exception->getMessage(),
            'correlation_id' => $this->correlationId,
            'attempts' => $this->attempts(),
        ]);
    }
}
