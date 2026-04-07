<?php declare(strict_types=1);

namespace App\Domains\Food\Jobs;

use Carbon\Carbon;



use Psr\Log\LoggerInterface;
use Illuminate\Http\Request;
final class OrderReadyReminderJob
{

    use Dispatchable;
        use InteractsWithQueue;
        use Queueable;
        use SerializesModels;

        public function __construct(
            private RestaurantOrder $order,
            private string $correlationId = '', private readonly Request $request, private readonly LoggerInterface $logger) {
            $this->onQueue('notifications');

        }

        public function handle(): void
        {
            try {
                $this->logger->info('Order ready reminder job started', [
                    'order_id' => $this->order->id,
                    'correlation_id' => $this->correlationId,
                ]);

                // Проверить статус заказа
                $order = RestaurantOrder::find($this->order->id);
                if (!$order || $order->status !== 'ready') {
                    $this->logger->notice('Order not in ready status', [
                        'order_id' => $this->order->id,
                        'status' => $order?->status,
                        'correlation_id' => $this->request?->header('X-Correlation-ID', \Illuminate\Support\Str::uuid()->toString()),
                    ]);

                    return;
                }
                // Notification::send($order->client, new OrderReadyNotification($order));

                $this->logger->info('Order ready reminder sent', [
                    'order_id' => $order->id,
                    'correlation_id' => $this->correlationId,
                ]);
            } catch (\Throwable $e) {
                $this->logger->error('Order ready reminder job failed', [
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
            return Carbon::now()->addHours(2);
        }
}
