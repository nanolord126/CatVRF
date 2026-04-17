<?php declare(strict_types=1);

namespace App\Domains\Logistics\Jobs;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\InteractsWithQueue;

use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\SerializesModels;


use Psr\Log\LoggerInterface;
final class UpdateCourierRouteJob
{


    use \Illuminate\Foundation\Bus\Dispatchable, \Illuminate\Queue\InteractsWithQueue, \Illuminate\Bus\Queueable, \Illuminate\Queue\SerializesModels;

        public int $tries = 3;
        public int $timeout = 120;

        public function __construct(
            private Courier $courier,
            private string $correlationId, private readonly LoggerInterface $logger
        ) {}

        public function handle(RouteOptimizationService $optimizationService): void
        {
            $this->logger->info('Updating courier route async', [
                'courier_id' => $this->courier->id,
                'correlation_id' => $this->correlationId
            ]);

            try {
                // Ищем активные заказы курьера
                $orderIds = $this->courier->deliveryOrders()
                    ->whereIn('status', ['assigned', 'picked_up'])
                    ->pluck('id')
                    ->toArray();

                if (empty($orderIds)) {
                    return;
                }

                // Вызов AI-оптимизатора
                $optimizationService->optimizeCourierRoute($this->courier, $orderIds);

            } catch (\Throwable $e) {
                $this->logger->error('Route update job failed', [
                    'courier_id' => $this->courier->id,
                    'error' => $e->getMessage(),
                    'correlation_id' => $this->correlationId
                ]);

                throw $e;
            }
        }

        public function tags(): array
        {
            return ['logistics', 'route_update', 'courier:' . $this->courier->id];
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
            'timestamp' => now()->toIso8601String(),
        ];
    }
}

