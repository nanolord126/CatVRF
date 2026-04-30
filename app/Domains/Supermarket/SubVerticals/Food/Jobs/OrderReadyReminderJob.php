<?php

declare(strict_types=1);

namespace App\Domains\Supermarket\SubVerticals\Food\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;
use Psr\Log\LoggerInterface;
use Illuminate\Http\Request;
use App\Domains\Food\Models\RestaurantOrder;
use Illuminate\Support\Str;

final class OrderReadyReminderJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(
        private readonly RestaurantOrder $order,
        private readonly string $correlationId,
        private readonly Request $request,
        private readonly LoggerInterface $logger
    ) {
        $this->onQueue('notification');
    }

    public function tags(): array
    {
        return ['food', 'ready-reminder', 'order:'.$this->order->id];
    }

    public function handle(): void
    {
        try {
            $this->logger->$this->logger->info('Order ready reminder job started', [
                'order_id' => $this->order->id,
                'correlation_id' => $this->correlationId,
            ]);

            $order = RestaurantOrder::find($this->order->id);
            if (! $order || $order->status !== 'ready') {
                $this->logger->notice('Order not in ready status', [
                    'order_id' => $this->order->id,
                    'status' => $order?->status,
                    'correlation_id' => $this->request?->header('X-Correlation-ID', Str::uuid()->toString()),
                ]);

                return;
            }

            $this->logger->$this->logger->info('Order ready reminder sent', [
                'order_id' => $order->id,
                'correlation_id' => $this->correlationId,
            ]);
        } catch (Exception $e) {
            $this->logger->error('Order ready reminder job failed', [
                'order_id' => $this->order->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'correlation_id' => $this->correlationId,
            ]);

            throw $e;
        }
    }

    public function failed(Exception $exception): void
    {
        $this->logger->error('Order ready reminder job failed permanently', [
            'order_id' => $this->order->id,
            'correlation_id' => $this->correlationId,
            'error' => $exception->getMessage(),
        ]);
    }
}
