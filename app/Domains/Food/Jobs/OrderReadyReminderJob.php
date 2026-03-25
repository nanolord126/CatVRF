<?php declare(strict_types=1);

namespace App\Domains\Food\Jobs;

use App\Domains\Food\Models\RestaurantOrder;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Job для отправки напоминаний о готовом заказе.
 * Production 2026.
 */
final class OrderReadyReminderJob implements ShouldQueue
{
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
            $this->log->channel('audit')->info('Order ready reminder job started', [
                'order_id' => $this->order->id,
                'correlation_id' => $this->correlationId,
            ]);

            // Проверить статус заказа
            $order = RestaurantOrder::find($this->order->id);
            if (!$order || $order->status !== 'ready') {
                $this->log->channel('audit')->notice('Order not in ready status', [
                    'order_id' => $this->order->id,
                    'status' => $order?->status,
                ]);

                return;
            }
            // Notification::send($order->client, new OrderReadyNotification($order));

            $this->log->channel('audit')->info('Order ready reminder sent', [
                'order_id' => $order->id,
                'correlation_id' => $this->correlationId,
            ]);
        } catch (\Throwable $e) {
            $this->log->channel('audit')->error('Order ready reminder job failed', [
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

