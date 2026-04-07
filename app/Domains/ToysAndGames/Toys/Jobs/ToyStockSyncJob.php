<?php

declare(strict_types=1);

namespace App\Domains\ToysAndGames\Toys\Jobs;

use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
use Illuminate\Http\Request;
use Psr\Log\LoggerInterface;

final class ToyStockSyncJob
{

    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

        /**
         * @var int $tries Max attempts for inventory sync.
         */
        public int $tries = 3;

        public function __construct(private readonly int $storeId,
            private string $correlationId = '',
        private readonly Request $request, private readonly LoggerInterface $logger) {}

        /**
         * Execute the stock sync.
         * Transactional audit to prevent race conditions during warehouse updates.
         */
        public function handle(): void
        {
            $cid = $this->correlationId ?: (string) Str::uuid();

            $this->logger->info('Toy Stock Sync Started', [
                'store_id' => $this->storeId,
                'cid' => $cid,
                'correlation_id' => $this->request->header('X-Correlation-ID', $this->correlationId ?? ''),
            ]);

            $store = ToyStore::findOrFail($this->storeId);
            $toys = $store->toys()->lockForUpdate()->get();

            foreach ($toys as $toy) {
                // Simulated inventory check logic (could be external API call)
                // If stock < 5, trigger automated reorder flag/audit
                if ($toy->stock_quantity < 5) {
                    $this->logger->warning('Low Stock Alert: Mandatory Reorder Needed', [
                        'toy_id' => $toy->id,
                        'sku' => $toy->sku,
                        'qty' => $toy->stock_quantity,
                        'cid' => $cid,
                'correlation_id' => $this->request->header('X-Correlation-ID', $this->correlationId ?? ''),
            ]);

                    $toy->update([
                        'tags' => array_unique(array_merge($toy->tags ?? [], ['reorder_needed']))
                    ]);
                }
            }

            $this->logger->info('Toy Stock Sync Completed Successfully', [
                'toys_processed' => count($toys),
                'cid' => $cid,
                'correlation_id' => $this->request->header('X-Correlation-ID', $this->correlationId ?? ''),
            ]);
        }

        /**
         * Handle job failure.
         */
        public function failed(\Throwable $exception): void
        {
            $this->logger->error('Toy Stock Sync JOB FAILED', [
                'store_id' => $this->storeId,
                'error' => $exception->getMessage(),
                'trace' => $exception->getTraceAsString(),
                'correlation_id' => $this->request->header('X-Correlation-ID', $this->correlationId ?? ''),
            ]);
        }
    }
