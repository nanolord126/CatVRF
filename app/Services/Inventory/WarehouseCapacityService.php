<?php

declare(strict_types=1);

namespace App\Services\Inventory;

use App\Services\Security\AuditService;
use App\Traits\WithAuditLogging;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Facades\Cache;
use Psr\Log\LoggerInterface;

/**
 * Warehouse Capacity Service
 *
 * Manages warehouse capacity planning:
 * - Track warehouse capacity utilization
 * - Capacity forecasting
 * - Expansion recommendations
 * - Slot optimization
 *
 * @author CatVRF Team
 * @version 2026.04.28
 */
final readonly class WarehouseCapacityService
{
    use WithAuditLogging;

    public function __construct(
        private readonly DatabaseManager $db,
        private readonly LoggerInterface $logger,
        private readonly AuditService $auditService,
    ) {}

    /**
     * Get warehouse capacity utilization
     *
     * @param  int  $warehouseId  Warehouse ID
     * @return array Capacity data
     */
    public function getCapacityUtilization(int $warehouseId): array
    {
        $cacheKey = "warehouse_capacity:{$warehouseId}";

        return Cache::remember($cacheKey, 600, function () use ($warehouseId) {
            $warehouse = $this->db->table('warehouses')
                ->where('id', $warehouseId)
                ->first();

            if (! $warehouse) {
                throw new \RuntimeException("Warehouse {$warehouseId} not found");
            }

            $locations = $this->db->table('warehouse_locations')
                ->where('warehouse_id', $warehouseId)
                ->where('is_active', true)
                ->get();

            $totalCapacity = $locations->sum('capacity');
            $totalUsage = $locations->sum('current_usage');
            $utilizationRate = $totalCapacity > 0 ? ($totalUsage / $totalCapacity) * 100 : 0;

            $zoneUtilization = $this->getZoneUtilization($warehouseId);

            $capacityByType = $locations->groupBy('type')->map(function ($locs) {
                return [
                    'count' => $locs->count(),
                    'total_capacity' => $locs->sum('capacity'),
                    'total_usage' => $locs->sum('current_usage'),
                    'utilization_rate' => $locs->sum('capacity') > 0
                        ? ($locs->sum('current_usage') / $locs->sum('capacity')) * 100
                        : 0,
                ];
            });

            return [
                'warehouse_id' => $warehouseId,
                'warehouse_name' => $warehouse->name,
                'total_locations' => $locations->count(),
                'total_capacity' => $totalCapacity,
                'total_usage' => $totalUsage,
                'utilization_rate' => round($utilizationRate, 2),
                'available_capacity' => max(0, $totalCapacity - $totalUsage),
                'zone_utilization' => $zoneUtilization,
                'capacity_by_type' => $capacityByType,
                'capacity_status' => $this->determineCapacityStatus($utilizationRate),
                'calculated_at' => now()->toIso8601String(),
            ];
        });
    }

    /**
     * Get zone utilization breakdown
     *
     * @param  int  $warehouseId  Warehouse ID
     * @return array Zone utilization
     */
    public function getZoneUtilization(int $warehouseId): array
    {
        $zones = $this->db->table('warehouse_zones')
            ->where('warehouse_id', $warehouseId)
            ->where('is_active', true)
            ->get();

        return $zones->map(function ($zone) {
            $locations = $this->db->table('warehouse_locations')
                ->where('zone_id', $zone->id)
                ->where('is_active', true)
                ->get();

            $totalCapacity = $locations->sum('capacity');
            $totalUsage = $locations->sum('current_usage');

            return [
                'zone_id' => $zone->id,
                'zone_code' => $zone->code,
                'zone_name' => $zone->name,
                'zone_type' => $zone->type,
                'location_count' => $locations->count(),
                'total_capacity' => $totalCapacity,
                'total_usage' => $totalUsage,
                'utilization_rate' => $totalCapacity > 0 ? ($totalUsage / $totalCapacity) * 100 : 0,
                'available_capacity' => max(0, $totalCapacity - $totalUsage),
            ];
        })->toArray();
    }

    /**
     * Forecast capacity needs
     *
     * @param  int  $warehouseId  Warehouse ID
     * @param  int  $forecastDays  Forecast period in days
     * @return array Forecast data
     */
    public function forecastCapacityNeeds(int $warehouseId, int $forecastDays = 90): array
    {
        $currentUtilization = $this->getCapacityUtilization($warehouseId);
        $currentRate = $currentUtilization['utilization_rate'];

        // Get historical growth rate
        $historicalGrowth = $this->calculateHistoricalGrowthRate($warehouseId, 90);

        // Project future utilization
        $forecast = [];
        $projectedRate = $currentRate;

        for ($day = 1; $day <= $forecastDays; $day++) {
            $projectedRate *= (1 + ($historicalGrowth / 365));
            $forecast[] = [
                'day' => $day,
                'date' => now()->addDays($day)->toDateString(),
                'projected_utilization_rate' => round($projectedRate, 2),
                'projected_usage' => round(
                    ($currentUtilization['total_capacity'] * $projectedRate) / 100,
                    2
                ),
            ];
        }

        // Find when capacity will be exceeded
        $capacityExceededDate = null;
        foreach ($forecast as $day) {
            if ($day['projected_utilization_rate'] >= 95) {
                $capacityExceededDate = $day['date'];
                break;
            }
        }

        return [
            'warehouse_id' => $warehouseId,
            'current_utilization_rate' => $currentRate,
            'historical_growth_rate_90d' => $historicalGrowth,
            'forecast_days' => $forecastDays,
            'capacity_exceeded_date' => $capacityExceededDate,
            'days_until_capacity_exceeded' => $capacityExceededDate
                ? now()->diffInDays($capacityExceededDate)
                : null,
            'forecast' => $forecast,
            'recommendation' => $this->generateCapacityRecommendation(
                $currentRate,
                $historicalGrowth,
                $capacityExceededDate
            ),
            'generated_at' => now()->toIso8601String(),
        ];
    }

    /**
     * Get slot optimization recommendations
     *
     * @param  int  $warehouseId  Warehouse ID
     * @return array Recommendations
     */
    public function getSlotOptimizationRecommendations(int $warehouseId): array
    {
        $locations = $this->db->table('warehouse_locations as l')
            ->join('warehouse_zones as z', 'l.zone_id', '=', 'z.id')
            ->where('l.warehouse_id', $warehouseId)
            ->where('l.is_active', true)
            ->select('l.*', 'z.type as zone_type')
            ->get();

        $recommendations = [];

        // Identify underutilized slots
        $underutilized = $locations->filter(fn ($loc) => $loc->current_usage < ($loc->capacity * 0.2));
        if ($underutilized->count() > 0) {
            $recommendations[] = [
                'type' => 'consolidation',
                'priority' => 'medium',
                'message' => "Consolidate items from {$underutilized->count()} underutilized slots",
                'affected_locations' => $underutilized->pluck('code')->toArray(),
            ];
        }

        // Identify overcapacity slots
        $overcapacity = $locations->filter(fn ($loc) => $loc->current_usage > $loc->capacity);
        if ($overcapacity->count() > 0) {
            $recommendations[] = [
                'type' => 'capacity_violation',
                'priority' => 'high',
                'message' => "Resolve overcapacity in {$overcapacity->count()} slots",
                'affected_locations' => $overcapacity->pluck('code')->toArray(),
            ];
        }

        // Identify misplaced items (fast movers in storage zones)
        $fastMoversInStorage = $this->db->table('location_items as li')
            ->join('warehouse_locations as l', 'li.location_id', '=', 'l.id')
            ->join('warehouse_zones as z', 'l.zone_id', '=', 'z.id')
            ->join('inventory_items as i', 'li.inventory_item_id', '=', 'i.id')
            ->where('l.warehouse_id', $warehouseId)
            ->where('z.type', 'storage')
            ->where('i.abc_class', 'A')
            ->count();

        if ($fastMoversInStorage > 0) {
            $recommendations[] = [
                'type' => 'slot_optimization',
                'priority' => 'high',
                'message' => "Move {$fastMoversInStorage} fast-moving items from storage to picking zones",
            ];
        }

        return [
            'warehouse_id' => $warehouseId,
            'total_locations' => $locations->count(),
            'underutilized_count' => $underutilized->count(),
            'overcapacity_count' => $overcapacity->count(),
            'recommendations' => $recommendations,
        ];
    }

    /**
     * Calculate warehouse storage efficiency
     *
     * @param  int  $warehouseId  Warehouse ID
     * @return array Efficiency metrics
     */
    public function calculateStorageEfficiency(int $warehouseId): array
    {
        $locations = $this->db->table('warehouse_locations')
            ->where('warehouse_id', $warehouseId)
            ->where('is_active', true)
            ->get();

        $totalLocations = $locations->count();
        $occupiedLocations = $locations->filter(fn ($loc) => $loc->current_usage > 0)->count();
        $occupancyRate = $totalLocations > 0 ? ($occupiedLocations / $totalLocations) * 100 : 0;

        $totalCapacity = $locations->sum('capacity');
        $totalUsage = $locations->sum('current_usage');
        $spaceUtilization = $totalCapacity > 0 ? ($totalUsage / $totalCapacity) * 100 : 0;

        // Calculate fill rate (average utilization of occupied locations)
        $occupiedWithUsage = $locations->filter(fn ($loc) => $loc->current_usage > 0);
        $avgFillRate = $occupiedWithUsage->isNotEmpty()
            ? $occupiedWithUsage->avg(fn ($loc) => $loc->capacity > 0 ? ($loc->current_usage / $loc->capacity) * 100 : 0)
            : 0;

        return [
            'warehouse_id' => $warehouseId,
            'total_locations' => $totalLocations,
            'occupied_locations' => $occupiedLocations,
            'occupancy_rate' => round($occupancyRate, 2),
            'total_capacity' => $totalCapacity,
            'total_usage' => $totalUsage,
            'space_utilization' => round($spaceUtilization, 2),
            'average_fill_rate' => round($avgFillRate, 2),
            'efficiency_score' => $this->calculateEfficiencyScore($occupancyRate, $spaceUtilization, $avgFillRate),
            'calculated_at' => now()->toIso8601String(),
        ];
    }

    /**
     * Determine capacity status
     *
     * @param  float  $utilizationRate  Utilization rate
     * @return string Status
     */
    private function determineCapacityStatus(float $utilizationRate): string
    {
        if ($utilizationRate >= 95) {
            return 'critical';
        }

        if ($utilizationRate >= 85) {
            return 'high';
        }

        if ($utilizationRate >= 70) {
            return 'moderate';
        }

        if ($utilizationRate >= 50) {
            return 'optimal';
        }

        return 'low';
    }

    /**
     * Calculate historical growth rate
     *
     * @param  int  $warehouseId  Warehouse ID
     * @param  int  $days  Number of days
     * @return float Growth rate (percentage)
     */
    private function calculateHistoricalGrowthRate(int $warehouseId, int $days): float
    {
        $currentUsage = $this->db->table('warehouse_locations')
            ->where('warehouse_id', $warehouseId)
            ->sum('current_usage');

        $pastUsage = $this->db->table('stock_movement_history')
            ->where('warehouse_id', $warehouseId)
            ->where('created_at', '>=', now()->subDays($days))
            ->where('created_at', '<=', now()->subDays($days))
            ->sum('usage');

        if ($pastUsage <= 0) {
            return 0.0;
        }

        $growthRate = (($currentUsage - $pastUsage) / $pastUsage) * 100;

        return $growthRate / $days * 365; // Annualized
    }

    /**
     * Generate capacity recommendation
     *
     * @param  float  $currentRate  Current utilization rate
     * @param  float  $growthRate  Growth rate
     * @param  string|null  $exceededDate  Capacity exceeded date
     * @return string Recommendation
     */
    private function generateCapacityRecommendation(
        float $currentRate,
        float $growthRate,
        ?string $exceededDate
    ): string {
        if ($currentRate >= 95) {
            return 'URGENT: Immediate capacity expansion required. Consider opening new warehouse or outsourcing.';
        }

        if ($currentRate >= 85) {
            return 'HIGH: Plan capacity expansion within 30 days. Optimize slot utilization.';
        }

        if ($exceededDate) {
            $daysUntil = now()->diffInDays($exceededDate);
            return "Plan capacity expansion within {$daysUntil} days based on current growth rate of {$growthRate}%.";
        }

        if ($growthRate > 20) {
            return 'Monitor growth closely. Begin planning for expansion in 6-12 months.';
        }

        return 'Capacity utilization is healthy. Continue monitoring.';
    }

    /**
     * Calculate efficiency score (0-100)
     *
     * @param  float  $occupancyRate  Occupancy rate
     * @param  float  $spaceUtilization  Space utilization
     * @param  float  $avgFillRate  Average fill rate
     * @return float Efficiency score
     */
    private function calculateEfficiencyScore(
        float $occupancyRate,
        float $spaceUtilization,
        float $avgFillRate
    ): float {
        $occupancyScore = $occupancyRate * 0.3;
        $utilizationScore = $spaceUtilization * 0.4;
        $fillScore = $avgFillRate * 0.3;

        return round($occupancyScore + $utilizationScore + $fillScore, 2);
    }
}
