<?php declare(strict_types=1);

namespace App\Domains\Food\Jobs;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\InteractsWithQueue;

use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\SerializesModels;

use Carbon\Carbon;


use Psr\Log\LoggerInterface;
final class AutoCloseOrderJob
{


        public function __construct(
            private RestaurantOrder $order,
            private string $correlationId = '', private readonly LoggerInterface $logger) {
            $this->onQueue('default');

        }

        public function handle(): void
        {
            try {
                $this->logger->info('Auto close order job started', [
                    'order_id' => $this->order->id,
                    'correlation_id' => $this->correlationId,
                ]);

                $order = RestaurantOrder::find($this->order->id);
                if (!$order || $order->status === 'delivered' || $order->status === 'cancelled') {
                    return;
                }

                // Если заказ готов > 2 часов → автоматически закрыть
                if ($order->ready_at && $order->ready_at->addHours(2)->isPast()) {
                    $order->update(['status' => 'delivered', 'completed_at' => Carbon::now()]);

                    $this->logger->info('Order auto-closed', [
                        'order_id' => $order->id,
                        'correlation_id' => $this->correlationId,
                    ]);
                }
            } catch (\Throwable $e) {
                $this->logger->error('Auto close order job failed', [
                    'order_id' => $this->order->id,
                    'error' => $e->getMessage(),
                    'correlation_id' => $this->correlationId,
                ]);

                throw $e;
            }
        }

        public function retryUntil(): Carbon
        {
            return Carbon::now()->addHours(3);
        }
}
