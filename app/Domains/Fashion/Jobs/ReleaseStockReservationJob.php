<?php declare(strict_types=1);

namespace App\Domains\Fashion\Jobs;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class ReleaseStockReservationJob extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

        public function __construct(
            private readonly int $productId,
            private readonly int $quantity,
            private readonly string $correlationId
        ) {}

        public function handle(): void
        {
            Log::channel('audit')->info('Release stock reservation job started', [
                'product_id' => $this->productId,
                'quantity' => $this->quantity,
                'correlation_id' => $this->correlationId,
            ]);

            try {
                DB::transaction(function () {
                    $product = FashionProduct::lockForUpdate()->find($this->productId);

                    if (!$product) {
                        Log::channel('audit')->error('Product not found for reservation release', [
                            'product_id' => $this->productId,
                            'correlation_id' => $this->correlationId,
                        ]);
                        return;
                    }

                    $newReserve = max(0, $product->reserve_quantity - $this->quantity);
                    $product->update(['reserve_quantity' => $newReserve]);

                    Log::channel('audit')->info('Stock reservation released', [
                        'product_id' => $this->productId,
                        'new_reserve' => $product->reserve_quantity,
                        'correlation_id' => $this->correlationId,
                    ]);
                });
            } catch (\Throwable $e) {
                Log::channel('audit')->error('Stock release failed', [
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
