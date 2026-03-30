<?php declare(strict_types=1);

namespace App\Domains\Food\Jobs;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class AutoCloseOrderJob extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    use Dispatchable;
        use InteractsWithQueue;
        use Queueable;
        use SerializesModels;

        public function __construct(
            private RestaurantOrder $order,
            private string $correlationId = '',
        ) {
            $this->onQueue('default');

        }

        public function handle(): void
        {
            try {
                Log::channel('audit')->info('Auto close order job started', [
                    'order_id' => $this->order->id,
                    'correlation_id' => $this->correlationId,
                ]);

                $order = RestaurantOrder::find($this->order->id);
                if (!$order || $order->status === 'delivered' || $order->status === 'cancelled') {
                    return;
                }

                // Если заказ готов > 2 часов → автоматически закрыть
                if ($order->ready_at && $order->ready_at->addHours(2)->isPast()) {
                    $order->update(['status' => 'delivered', 'completed_at' => now()]);

                    Log::channel('audit')->info('Order auto-closed', [
                        'order_id' => $order->id,
                        'correlation_id' => $this->correlationId,
                    ]);
                }
            } catch (\Throwable $e) {
                Log::channel('audit')->error('Auto close order job failed', [
                    'order_id' => $this->order->id,
                    'error' => $e->getMessage(),
                    'correlation_id' => $this->correlationId,
                ]);

                throw $e;
            }
        }

        public function retryUntil(): Carbon
        {
            return now()->addHours(3);
        }
}
