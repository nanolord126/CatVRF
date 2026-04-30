<?php

declare(strict_types=1);

namespace Modules\Restaurant\Application\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Carbon\CarbonImmutable;
use Modules\Restaurant\Domain\Repositories\IoTTelemetryRepositoryInterface;

final class CleanupOldTelemetryJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 300; // 5 minutes for large cleanup

    public function __construct(
        private readonly int $retentionDays = 90,
    ) {}

    public function handle(IoTTelemetryRepositoryInterface $telemetryRepository): void
    {
        try {
            $cutoffDate = CarbonImmutable::now()->subDays($this->retentionDays);
            $deletedCount = $telemetryRepository->deleteOlderThan($cutoffDate);
            
            Log::info('Old IoT telemetry cleaned up', [
                'retention_days' => $this->retentionDays,
                'cutoff_date' => $cutoffDate->toIso8601String(),
                'deleted_count' => $deletedCount,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to cleanup old telemetry', [
                'error' => $e->getMessage(),
            ]);
        }
    }
}
