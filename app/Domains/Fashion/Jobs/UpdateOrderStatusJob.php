<?php declare(strict_types=1);

namespace App\Domains\Fashion\Jobs;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class UpdateOrderStatusJob extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

        public function __construct(
            private readonly int $orderId = 0,
            private readonly string $status = '',
            private readonly string $correlationId = '',
        ) {
            $this->onQueue('default');
        }

        public function handle(): void
        {
            try {
                $order = FashionOrder::findOrFail($this->orderId);

                $order->update([
                    'status' => $this->status,
                    'correlation_id' => $this->correlationId,
                ]);

                if ($this->status === 'shipped') {
                    $order->update(['shipped_at' => now()]);
                } elseif ($this->status === 'delivered') {
                    $order->update(['delivered_at' => now()]);
                }

                Log::channel('audit')->info('Fashion order status updated via job', [
                    'order_id' => $this->orderId,
                    'status' => $this->status,
                    'correlation_id' => $this->correlationId,
                ]);
            } catch (Throwable $e) {
                Log::channel('audit')->error('Failed to update fashion order status', [
                    'order_id' => $this->orderId,
                    'error' => $e->getMessage(),
                    'correlation_id' => $this->correlationId,
                ]);

                throw $e;
            }
        }

        public function retryUntil(): \DateTime
        {
            return now()->addHours(4);
        }
}
