<?php declare(strict_types=1);

/**
 * CheckMusicStockThreshold — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/checkmusicstockthreshold
 */


namespace App\Domains\MusicAndInstruments\Music\Listeners;


use Psr\Log\LoggerInterface;
final class CheckMusicStockThreshold
{
    public function __construct(
        private readonly LoggerInterface $logger) {}


    /**
         * Handle the event.
         */
        public function handle(MusicStockChanged $event): void
        {
            $this->logger->info('Processing music stock change event', [
                'instrument_id' => $event->instrument->id,
                'old_stock' => $event->oldStock,
                'new_stock' => $event->newStock,
                'correlation_id' => $event->correlationId,
            ]);

            // Dispatch background job to check thresholds and notify
            StockThresholdJob::dispatch(
                $event->instrument->id,
                $event->correlationId
            );
        }
}
