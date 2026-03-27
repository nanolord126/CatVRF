<?php declare(strict_types=1);

namespace App\Domains\Food\Services;

use Illuminate\Support\Facades\Log;

use App\Domains\Food\Models\DeliveryZone;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

final class DeliverySurgeService
{
    private const SURGE_CACHE_TTL = 300; // 5 минут

    public function __construct()
    {
    }

    /**
     * Получить коэффициент surge для зоны доставки
     */
    public function getSurgeMultiplier(string $zoneId, string $correlationId): float
    {


        $cacheKey = "delivery_surge:zone:{$zoneId}";
        
        $multiplier = Cache::get($cacheKey, 1.0);
        
        Log::channel('audit')->info('Delivery surge multiplier retrieved', [
            'zone_id' => $zoneId,
            'multiplier' => $multiplier,
            'correlation_id' => $correlationId,
        ]);

        return (float) $multiplier;
    }

    /**
     * Пересчитать surge для всех зон на основе спроса
     */
    public function recalculateSurges(string $correlationId): array
    {


        $results = [];

        try {
            DB::transaction(function () use (&$results, $correlationId) {
                $zones = DeliveryZone::all();

                foreach ($zones as $zone) {
                    $demandFactor = $this->calculateDemandFactor($zone->id);
                    $multiplier = max(1.0, min(2.5, 1.0 + ($demandFactor * 1.5)));

                    Cache::put("delivery_surge:zone:{$zone->id}", $multiplier, self::SURGE_CACHE_TTL);

                    $results[$zone->id] = $multiplier;

                    Log::channel('audit')->info('Delivery surge recalculated', [
                        'zone_id' => $zone->id,
                        'demand_factor' => $demandFactor,
                        'multiplier' => $multiplier,
                        'correlation_id' => $correlationId,
                    ]);
                }
            });
        } catch (\Exception $e) {
            Log::channel('audit')->error('Delivery surge recalculation failed', [
                'error' => $e->getMessage(),
                'correlation_id' => $correlationId,
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }

        return $results;
    }

    /**
     * Рассчитать коэффициент спроса для зоны доставки
     */
    private function calculateDemandFactor(string $zoneId): float
    {
        $orderCount5Min = DB::table('restaurant_orders')
            ->where('delivery_zone_id', $zoneId)
            ->where('created_at', '>=', Carbon::now()->subMinutes(5))
            ->where('status', 'pending')
            ->count();

        $averageOrdersPerHour = max(1, $orderCount5Min * 12);
        $demandFactor = min(1.0, $averageOrdersPerHour / 30);

        return $demandFactor;
    }
}
