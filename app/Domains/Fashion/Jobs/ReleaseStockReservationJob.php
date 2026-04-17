<?php declare(strict_types=1);

namespace App\Domains\Fashion\Jobs;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\InteractsWithQueue;

use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\SerializesModels;


use Psr\Log\LoggerInterface;
final class ReleaseStockReservationJob
{


    use \Illuminate\Foundation\Bus\Dispatchable, \Illuminate\Queue\InteractsWithQueue, \Illuminate\Bus\Queueable, \Illuminate\Queue\SerializesModels;

        public function __construct(private readonly int $productId,
            private readonly int $quantity,
            private readonly string $correlationId
       ,
        private readonly FraudControlService $fraud,
        private readonly \Illuminate\Database\DatabaseManager $db, private readonly LoggerInterface $logger) {}

        public function handle(): void
        {
            $this->logger->info('Release stock reservation job started', [
                'product_id' => $this->productId,
                'quantity' => $this->quantity,
                'correlation_id' => $this->correlationId,
            ]);

            try {
                $this->db->transaction(function () {
                    $product = FashionProduct::lockForUpdate()->find($this->productId);

                    if (!$product) {
                        $this->logger->error('Product not found for reservation release', [
                            'product_id' => $this->productId,
                            'correlation_id' => $this->correlationId,
                        ]);
                        return;
                    }

                    $newReserve = max(0, $product->reserve_quantity - $this->quantity);
                    $product->update(['reserve_quantity' => $newReserve]);

                    $this->logger->info('Stock reservation released', [
                        'product_id' => $this->productId,
                        'new_reserve' => $product->reserve_quantity,
                        'correlation_id' => $this->correlationId,
                    ]);
                });
            } catch (\Throwable $e) {
                $this->logger->error('Stock release failed', [
                    'error' => $e->getMessage(),
                    'correlation_id' => $this->correlationId,
                ]);
                throw $e;
            }
        }

        public function tags(): array
        {
            return ['fashion', 'reservation', "product:{$this->productId}", $this->correlationId];
        }
}

