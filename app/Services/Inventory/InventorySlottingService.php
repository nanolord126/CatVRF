<?php

declare(strict_types=1);

namespace App\Services\Inventory;

use App\Services\Security\AuditService;
use App\Traits\WithAuditLogging;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Facades\Cache;
use Psr\Log\LoggerInterface;

/**
 * Inventory Slotting Service
 *
 * Manages warehouse slotting optimization:
 * - Calculate optimal slot assignments
 * - Slot velocity analysis
 * - Slot size optimization
 * - Zone assignment recommendations
 *
 * @author CatVRF Team
 * @version 2026.04.28
 */
final readonly class InventorySlottingService
{
    use WithAuditLogging;

    public function __construct(
        private readonly DatabaseManager $db,
        private readonly LoggerInterface $logger,
        private readonly AuditService $auditService,
    ) {}

    /**
     * Calculate optimal slot assignments
     *
     * @param  int  $warehouseId  Warehouse ID
     * @param  int  $userId  User ID
     * @param  int  $tenantId  Tenant ID
     * @return array Slot assignments
     */
    public function calculateOptimalSlots(int $warehouseId, int $userId, int $tenantId): array
    {
        $correlationId = \Illuminate\Support\Str::uuid()->toString();

        $cacheKey = "inventory_slotting:optimal:{$warehouseId}";

        $recommendations = Cache::remember($cacheKey, 3600, function () use ($warehouseId) {
            $items = $this->db->table('inventory_items')
                ->where('warehouse_id', $warehouseId)
                ->where('is_active', true)
                ->where('current_stock', '>', 0)
                ->get();

            $itemVelocities = [];

            foreach ($items as $item) {
                $velocity = $this->calculateItemVelocity($item->id, 90);
                $itemVelocities[] = [
                    'inventory_item_id' => $item->id,
                    'sku' => $item->sku,
                    'name' => $item->name,
                    'abc_class' => $item->abc_class,
                    'velocity' => $velocity,
                    'current_location_id' => $this->getCurrentLocationId($item->id),
                    'volume' => $item->volume_per_unit ?? 1,
                    'weight' => $item->weight_per_unit ?? 1,
                ];
            }

            usort($itemVelocities, fn ($a, $b) => $b['velocity'] <=> $a['velocity']);

            $availableLocations = $this->getAvailableLocations($warehouseId);

            $assignments = [];

            foreach ($itemVelocities as $item) {
                $optimalLocation = $this->findOptimalLocation($item, $availableLocations);

                if ($optimalLocation && $optimalLocation['location_id'] != $item['current_location_id']) {
                    $assignments[] = [
                        'inventory_item_id' => $item['inventory_item_id'],
                        'sku' => $item['sku'],
                        'current_location_id' => $item['current_location_id'],
                        'recommended_location_id' => $optimalLocation['location_id'],
                        'recommended_location_code' => $optimalLocation['location_code'],
                        'zone' => $optimalLocation['zone'],
                        'reason' => $optimalLocation['reason'],
                        'priority' => $this->calculateMovePriority($item),
                    ];
                }
            }

            return $assignments;
        });

        $this->logAction(
            action: 'optimal_slotting_calculated',
            entityType: 'InventorySlotting',
            entityId: $warehouseId,
            context: [
                'correlation_id' => $correlationId,
                'recommendations_count' => count($recommendations),
            ],
            userId: $userId,
            tenantId: $tenantId
        );

        return [
            'warehouse_id' => $warehouseId,
            'total_recommendations' => count($recommendations),
            'recommendations' => $recommendations,
            'calculated_at' => now()->toIso8601String(),
        ];
    }

    /**
     * Get slot velocity analysis
     *
     * @param  int  $warehouseId  Warehouse ID
     * @return array Velocity analysis
     */
    public function getSlotVelocityAnalysis(int $warehouseId): array
    {
        $locations = $this->db->table('warehouse_locations as l')
            ->join('warehouse_zones as z', 'l.zone_id', '=', 'z.id')
            ->where('l.warehouse_id', $warehouseId)
            ->where('l.is_active', true)
            ->select('l.*', 'z.type as zone_type', 'z.code as zone_code')
            ->get();

        $locationVelocities = [];

        foreach ($locations as $location) {
            $items = $this->db->table('location_items')
                ->where('location_id', $location->id)
                ->get();

            $totalVelocity = 0;

            foreach ($items as $item) {
                $totalVelocity += $this->calculateItemVelocity($item->inventory_item_id, 90);
            }

            $locationVelocities[] = [
                'location_id' => $location->id,
                'location_code' => $location->code,
                'zone_type' => $location->zone_type,
                'zone_code' => $location->zone_code,
                'item_count' => $items->count(),
                'total_velocity' => round($totalVelocity, 2),
                'average_velocity' => $items->count() > 0 ? round($totalVelocity / $items->count(), 2) : 0,
                'capacity' => $location->capacity,
                'current_usage' => $location->current_usage,
                'utilization' => $location->capacity > 0 ? ($location->current_usage / $location->capacity) * 100 : 0,
            ];
        }

        usort($locationVelocities, fn ($a, $b) => $b['total_velocity'] <=> $a['total_velocity']);

        return [
            'warehouse_id' => $warehouseId,
            'total_locations' => count($locationVelocities),
            'locations' => $locationVelocities,
            'generated_at' => now()->toIso8601String(),
        ];
    }

    /**
     * Get zone efficiency analysis
     *
     * @param  int  $warehouseId  Warehouse ID
     * @return array Zone efficiency
     */
    public function getZoneEfficiency(int $warehouseId): array
    {
        $zones = $this->db->table('warehouse_zones')
            ->where('warehouse_id', $warehouseId)
            ->where('is_active', true)
            ->get();

        $zoneAnalysis = [];

        foreach ($zones as $zone) {
            $locations = $this->db->table('warehouse_locations')
                ->where('zone_id', $zone->id)
                ->where('is_active', true)
                ->get();

            $zoneVelocity = 0;
            $zoneItems = 0;

            foreach ($locations as $location) {
                $items = $this->db->table('location_items')
                    ->where('location_id', $location->id)
                    ->get();

                foreach ($items as $item) {
                    $zoneVelocity += $this->calculateItemVelocity($item->inventory_item_id, 90);
                    $zoneItems++;
                }
            }

            $expectedVelocity = match ($zone->type) {
                'picking' => $zoneVelocity * 1.5,
                'storage' => $zoneVelocity * 0.8,
                'receiving' => $zoneVelocity * 1.2,
                'shipping' => $zoneVelocity * 1.3,
                default => $zoneVelocity,
            };

            $efficiencyScore = $expectedVelocity > 0 ? ($zoneVelocity / $expectedVelocity) * 100 : 0;

            $zoneAnalysis[] = [
                'zone_id' => $zone->id,
                'zone_code' => $zone->code,
                'zone_name' => $zone->name,
                'zone_type' => $zone->type,
                'location_count' => $locations->count(),
                'total_items' => $zoneItems,
                'total_velocity' => round($zoneVelocity, 2),
                'expected_velocity' => round($expectedVelocity, 2),
                'efficiency_score' => round($efficiencyScore, 2),
            ];
        }

        return [
            'warehouse_id' => $warehouseId,
            'zones' => $zoneAnalysis,
            'generated_at' => now()->toIso8601String(),
        ];
    }

    /**
     * Calculate item velocity
     *
     * @param  int  $inventoryItemId  Inventory item ID
     * @param  int  $days  Number of days
     * @return float Velocity
     */
    private function calculateItemVelocity(int $inventoryItemId, int $days): float
    {
        $movements = $this->db->table('stock_movements')
            ->where('inventory_item_id', $inventoryItemId)
            ->where('type', 'out')
            ->where('created_at', '>=', now()->subDays($days))
            ->sum('quantity');

        return $movements / $days;
    }

    /**
     * Get current location ID for item
     *
     * @param  int  $inventoryItemId  Inventory item ID
     * @return int|null Location ID
     */
    private function getCurrentLocationId(int $inventoryItemId): ?int
    {
        $locationItem = $this->db->table('location_items')
            ->where('inventory_item_id', $inventoryItemId)
            ->where('quantity', '>', 0)
            ->first();

        return $locationItem ? $locationItem->location_id : null;
    }

    /**
     * Get available locations
     *
     * @param  int  $warehouseId  Warehouse ID
     * @return array Available locations
     */
    private function getAvailableLocations(int $warehouseId): array
    {
        $locations = $this->db->table('warehouse_locations as l')
            ->join('warehouse_zones as z', 'l.zone_id', '=', 'z.id')
            ->where('l.warehouse_id', $warehouseId)
            ->where('l.is_active', true)
            ->select('l.*', 'z.type as zone_type', 'z.code as zone_code')
            ->get();

        return $locations->map(fn ($l) => [
            'location_id' => $l->id,
            'location_code' => $l->code,
            'zone' => $l->zone_type,
            'zone_code' => $l->zone_code,
            'capacity' => $l->capacity,
            'current_usage' => $l->current_usage,
            'available_capacity' => $l->capacity - $l->current_usage,
        ])->toArray();
    }

    /**
     * Find optimal location for item
     *
     * @param  array  $item  Item data
     * @param  array  $availableLocations  Available locations
     * @return array|null Optimal location
     */
    private function findOptimalLocation(array $item, array $availableLocations): ?array
    {
        $targetZone = match ($item['abc_class']) {
            'A' => 'picking',
            'B' => 'picking',
            'C' => 'storage',
            default => 'storage',
        };

        $suitableLocations = array_filter($availableLocations, fn ($l) =>
            $l['zone'] === $targetZone &&
            $l['available_capacity'] >= $item['volume']
        );

        if (empty($suitableLocations)) {
            $suitableLocations = array_filter($availableLocations, fn ($l) =>
                $l['available_capacity'] >= $item['volume']
            );
        }

        if (empty($suitableLocations)) {
            return null;
        }

        usort($suitableLocations, fn ($a, $b) =>
            ($a['current_usage'] / $a['capacity']) <=> ($b['current_usage'] / $b['capacity'])
        );

        $bestLocation = $suitableLocations[0];

        return [
            'location_id' => $bestLocation['location_id'],
            'location_code' => $bestLocation['location_code'],
            'zone' => $bestLocation['zone'],
            'reason' => "Optimal for {$item['abc_class']}-class item with velocity {$item['velocity']}",
        ];
    }

    /**
     * Calculate move priority
     *
     * @param  array  $item  Item data
     * @return int Priority (1-10)
     */
    private function calculateMovePriority(array $item): int
    {
        $priority = 5;

        if ($item['abc_class'] === 'A' && $item['velocity'] > 10) {
            $priority = 10;
        } elseif ($item['abc_class'] === 'A') {
            $priority = 8;
        } elseif ($item['abc_class'] === 'B') {
            $priority = 6;
        }

        return $priority;
    }
}
