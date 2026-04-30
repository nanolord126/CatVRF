<?php

declare(strict_types=1);

namespace App\Domains\Fashion\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Carbon\Carbon;
use Illuminate\Contracts\Queue\ShouldQueue;
use Psr\Log\LoggerInterface;
use DateTime;

final class UpdateOrderStatusJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(
        private readonly int $orderId,
        private readonly string $status,
        private readonly string $correlationId,
        private readonly LoggerInterface $logger
    ) {
        $this->onQueue('default');
    }

    public function tags(): array
    {
        return ['fashion', 'job'];
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
        return (new \Carbon\Carbon())->addHours(4);
    }

    /**
     * Get the string representation of this instance.
     *
     * @return string The string representation
     */
    public function __toString(): string
    {
        return self::class;
    }

    /**
     * Get debug information for this instance.
     *
     * @return array<string, mixed> Debug data including class name and state
     */
    public function toDebugArray(): array
    {
        return [
            'class' => self::class,
            'timestamp' => now()->toIso8601String(),
        ];
    }

    public function failed(\Throwable $exception): void
    {
        $this->logger->error('fashion job failed', [
            'error' => $exception->getMessage(),
        ]);
    }
}
