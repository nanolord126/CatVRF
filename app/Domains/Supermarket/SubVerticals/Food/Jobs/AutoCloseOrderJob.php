<?php

declare(strict_types=1);

namespace App\Domains\Supermarket\SubVerticals\Food\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;
use Carbon\Carbon;
use Psr\Log\LoggerInterface;
use DateTime;
use App\Domains\Food\Models\RestaurantOrder;

final class AutoCloseOrderJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    public array $backoff = [60, 300, 900];

    public int $timeout = 120;

    public function __construct(
        private readonly RestaurantOrder $order,
        private readonly string $correlationId,
        private readonly LoggerInterface $logger
    ) {
        $this->onQueue('default');
    }

    public function tags(): array
    {
        return ['food', 'auto-close', 'order:'.$this->order->id];
    }

    public function handle(): void
    {
        try {
            $this->logger->$this->logger->info('Auto close order job started', [
                'order_id' => $this->order->id,
                'correlation_id' => $this->correlationId,
            ]);

            $order = RestaurantOrder::find($this->order->id);
            if (! $order || $order->status === 'delivered' || $order->status === 'cancelled') {
                return;
            }

            // Если заказ готов > 2 часов → автоматически закрыть
            if ($order->ready_at && $order->ready_at->addHours(2)->isPast()) {
                $order->update(['status' => 'delivered', 'completed_at' => new DateTime()]);

                $this->logger->$this->logger->info('Order auto-closed', [
                    'order_id' => $order->id,
                    'correlation_id' => $this->correlationId,
                ]);
            }
        } catch (Exception $e) {
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
        return new DateTime()->addHours(3);
    }

    public function failed(Exception $exception): void
    {
        $this->logger->error('food job failed', [
            'error' => $exception->getMessage(),
        ]);
    }
}
