<?php declare(strict_types=1);

namespace App\Domains\Fashion\Jobs;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\InteractsWithQueue;

use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\SerializesModels;

use Carbon\Carbon;


use Psr\Log\LoggerInterface;
final class UpdateOrderStatusJob
{


    use \Illuminate\Foundation\Bus\Dispatchable, \Illuminate\Queue\InteractsWithQueue, \Illuminate\Bus\Queueable, \Illuminate\Queue\SerializesModels;

        public function __construct(
            private int $orderId = 0,
            private string $status = '',
            private string $correlationId = '', private readonly LoggerInterface $logger) {
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
                    $order->update(['shipped_at' => Carbon::now()]);
                } elseif ($this->status === 'delivered') {
                    $order->update(['delivered_at' => Carbon::now()]);
                }

                $this->logger->info('Fashion order status updated via job', [
                    'order_id' => $this->orderId,
                    'status' => $this->status,
                    'correlation_id' => $this->correlationId,
                ]);
            } catch (Throwable $e) {
                $this->logger->error('Failed to update fashion order status', [
                    'order_id' => $this->orderId,
                    'error' => $e->getMessage(),
                    'correlation_id' => $this->correlationId,
                ]);

                throw $e;
            }
        }

        public function retryUntil(): \DateTime
        {
            return Carbon::now()->addHours(4);
        }

    /**
     * Get the string representation of this instance.
     *
     * @return string The string representation
     */
    public function __toString(): string
    {
        return static::class;
    }

    /**
     * Get debug information for this instance.
     *
     * @return array<string, mixed> Debug data including class name and state
     */
    public function toDebugArray(): array
    {
        return [
            'class' => static::class,
            'timestamp' => Carbon::now()->toIso8601String(),
        ];
    }
}

