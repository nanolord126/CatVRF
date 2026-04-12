<?php declare(strict_types=1);

namespace App\Jobs;


use App\Domains\Logistics\Models\Courier;
use App\Domains\Logistics\Models\DeliveryOrder;
use App\Services\Delivery\RouteOptimizationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Log\LogManager;


/**
 * RouteOptimizationJob — перерасчёт маршрутов курьеров каждые 3 минуты.
 *
 * Dispatched из:
 *   - Console\Kernel через schedule()->everyThreeMinutes()
 *   - CourierService::assignCourier() при новом назначении
 *   - GeotrackingService при критическом отклонении от маршрута (>500 м)
 */
final class RouteOptimizationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 2;
    public int $timeout = 60;

    /** @param int[] $orderIds */
    public function __construct(
        private readonly int   $courierId,
        private array $orderIds = [],
        private readonly LogManager $logger,
    ) {}

    public function handle(RouteOptimizationService $optimizer): void
    {
        $courier = Courier::where('id', $this->courierId)
            ->where('is_online', true)
            ->first();

        if ($courier === null) {
            // Курьер оффлайн — пропускаем
            return;
        }

        $orderIds = empty($this->orderIds)
            ? DeliveryOrder::where('courier_id', $this->courierId)
                ->whereIn('status', ['assigned', 'picked_up', 'in_transit'])
                ->pluck('id')
                ->toArray()
            : $this->orderIds;

        if (empty($orderIds)) {
            return;
        }

        try {
            $result = $optimizer->optimizeForCourier($this->courierId, $orderIds);

            $this->logger->channel('audit')->info('RouteOptimizationJob done', [
                'courier_id'       => $this->courierId,
                'orders'           => count($orderIds),
                'total_minutes'    => $result['total_minutes'],
                'total_km'         => $result['total_distance_km'],
            ]);
        } catch (\Throwable $e) {
            $this->logger->channel('audit')->error('RouteOptimizationJob failed', [
                'courier_id' => $this->courierId,
                'error'      => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    public function queue(): string
    {
        return 'route-opt';
    }
}
