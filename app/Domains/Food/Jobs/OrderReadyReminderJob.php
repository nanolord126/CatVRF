<?php declare(strict_types=1);

namespace App\Domains\Food\Jobs;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class OrderReadyReminderJob extends Model
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
            $this->onQueue('notifications');

        }

        public function handle(): void
        {
            try {
                Log::channel('audit')->info('Order ready reminder job started', [
                    'order_id' => $this->order->id,
                    'correlation_id' => $this->correlationId,
                ]);

                // Проверить статус заказа
                $order = RestaurantOrder::find($this->order->id);
                if (!$order || $order->status !== 'ready') {
                    Log::channel('audit')->notice('Order not in ready status', [
                        'order_id' => $this->order->id,
                        'status' => $order?->status,
                    ]);

                    return;
                }
                // Notification::send($order->client, new OrderReadyNotification($order));

                Log::channel('audit')->info('Order ready reminder sent', [
                    'order_id' => $order->id,
                    'correlation_id' => $this->correlationId,
                ]);
            } catch (\Throwable $e) {
                Log::channel('audit')->error('Order ready reminder job failed', [
                    'order_id' => $this->order->id,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                    'correlation_id' => $this->correlationId,
                ]);

                throw $e;
            }
        }

        public function retryUntil(): Carbon
        {
            return now()->addHours(2);
        }
}
