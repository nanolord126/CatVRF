<?php declare(strict_types=1);

namespace App\Domains\Fashion\Jobs;

use App\Domains\Fashion\Models\FashionOrder;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

final class UpdateOrderStatusJob implements ShouldQueue
{
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

            $this->log->channel('audit')->info('Fashion order status updated via job', [
                'order_id' => $this->orderId,
                'status' => $this->status,
                'correlation_id' => $this->correlationId,
            ]);
        } catch (Throwable $e) {
            $this->log->channel('audit')->error('Failed to update fashion order status', [
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
