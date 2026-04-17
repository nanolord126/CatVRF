<?php declare(strict_types=1);

namespace App\Domains\MusicAndInstruments\Music\Jobs;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\InteractsWithQueue;

use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\SerializesModels;


use Psr\Log\LoggerInterface;
final class StockThresholdJob
{


    use \Illuminate\Foundation\Bus\Dispatchable, \Illuminate\Queue\InteractsWithQueue, \Illuminate\Bus\Queueable, \Illuminate\Queue\SerializesModels;

        /**
         * Create a new job instance.
         */
        public function __construct(
            public int $instrumentId,
            public string $correlationId, private readonly LoggerInterface $logger
        ) {}

        /**
         * Execute the job.
         */
        public function handle(): void
        {
            $instrument = MusicInstrument::find($this->instrumentId);

            if (!$instrument) {
                return;
            }

            // Check if stock is below threshold
            if ($instrument->current_stock <= ($instrument->min_stock_threshold ?? 3)) {
                $this->logger->warning('Low stock detected for instrument', [
                    'instrument_id' => $instrument->id,
                    'name' => $instrument->name,
                    'stock' => $instrument->current_stock,
                    'threshold' => $instrument->min_stock_threshold,
                    'correlation_id' => $this->correlationId,
                ]);

                // Notification logic (mocked or integrated with platform)
                // Notification::send($instrument->tenant, new LowStockNotification($instrument));
            }

            if ($instrument->current_stock === 0) {
                $this->logger->critical('Stock exhausted for music instrument', [
                    'instrument_id' => $instrument->id,
                    'name' => $instrument->name,
                    'correlation_id' => $this->correlationId,
                ]);
            }
        }

        /**
         * Get tags for the job.
         */
        public function tags(): array
        {
            return ['music', 'stock', 'alert', 'instrument:' . $this->instrumentId];
        }
}

