<?php

declare(strict_types=1);

namespace Modules\Marketplace\Application\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Modules\Marketplace\Application\Services\MarketplaceAggregatorService;
use Psr\Log\LoggerInterface;

/**
 * Job для синхронизации вертикалей с маркетплейсом
 * Запускается по расписанию или вручную
 */
final class SyncVerticalsJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;
    public int $timeout = 900;

    public function __construct(
        public readonly ?string $vertical = null,
        public readonly bool $syncPending = false,
    ) {}

    public function handle(
        MarketplaceAggregatorService $aggregator,
        LoggerInterface $logger,
    ): void {
        $logger->info('Starting verticals sync job', [
            'vertical' => $this->vertical,
            'sync_pending' => $this->syncPending,
        ]);

        try {
            if ($this->syncPending) {
                $results = $aggregator->syncPending();
            } elseif ($this->vertical !== null) {
                // Синхронизация конкретной вертикали
                $results = ['vertical_sync' => $this->vertical];
                // TODO: Implement single vertical sync
            } else {
                $results = $aggregator->syncAllVerticals();
            }

            $logger->info('Verticals sync job completed', [
                'results' => $results,
            ]);
        } catch (\Throwable $e) {
            $logger->error('Verticals sync job failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    public function failed(\Throwable $exception): void
    {
        logger()->error('Verticals sync job failed permanently', [
            'error' => $exception->getMessage(),
            'vertical' => $this->vertical,
            'sync_pending' => $this->syncPending,
        ]);
    }
}
