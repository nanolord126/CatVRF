<?php declare(strict_types=1);

namespace App\Domains\Logistics\Jobs;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class UpdateCourierRouteJob extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

        public int $tries = 3;
        public int $timeout = 120;

        public function __construct(
            private Courier $courier,
            private string $correlationId
        ) {}

        public function handle(RouteOptimizationService $optimizationService): void
        {
            Log::channel('audit')->info('Updating courier route async', [
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
                Log::error('Route update job failed', [
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
}
