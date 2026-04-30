<?php

declare(strict_types=1);

namespace App\Services\Inventory;

use App\Services\Security\AuditService;
use App\Traits\WithAuditLogging;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Str;
use Psr\Log\LoggerInterface;

/**
 * Warehouse Location Service
 *
 * Manages warehouse locations, bins, and zones:
 * - Location hierarchy management (warehouse -> zone -> aisle -> shelf -> bin)
 * - Location capacity tracking
 * - Location utilization analysis
 * - Location optimization recommendations
 *
 * @author CatVRF Team
 * @version 2026.04.28
 */
final readonly class WarehouseLocationService
{
    use WithAuditLogging;

    public function __construct(
        private readonly DatabaseManager $db,
        private readonly LoggerInterface $logger,
        private readonly AuditService $auditService,
    ) {}

    /**
     * Create warehouse zone
     *
     * @param  int  $warehouseId  Warehouse ID
     * @param  string  $zoneCode  Zone code (e.g., 'A', 'B', 'COLD')
     * @param  string  $zoneName  Zone name
     * @param  string  $zoneType  Zone type (storage, picking, receiving, shipping)
     * @param  int  $userId  User ID
     * @param  int  $tenantId  Tenant ID
     * @return int Zone ID
     */
    public function createZone(
        int $warehouseId,
        string $zoneCode,
        string $zoneName,
        string $zoneType,
        int $userId,
        int $tenantId
    ): int {
        $correlationId = Str::uuid()->toString();

        return $this->db->transaction(function () use (
            $warehouseId,
            $zoneCode,
            $zoneName,
            $zoneType,
            $userId,
            $tenantId,
            $correlationId
        ) {
            $existing = $this->db->table('warehouse_zones')
                ->where('warehouse_id', $warehouseId)
                ->where('code', $zoneCode)
                ->first();

            if ($existing) {
                throw new \RuntimeException("Zone {$zoneCode} already exists in warehouse");
            }

            $zoneId = $this->db->table('warehouse_zones')->insertGetId([
                'uuid' => Str::uuid()->toString(),
                'warehouse_id' => $warehouseId,
                'code' => $zoneCode,
                'name' => $zoneName,
                'type' => $zoneType,
                'tenant_id' => $tenantId,
                'is_active' => true,
                'created_by' => $userId,
                'created_at' => now(),
            ]);

            $this->logCreated(
                entityType: 'WarehouseZone',
                entityId: $zoneId,
                context: [
                    'correlation_id' => $correlationId,
                    'warehouse_id' => $warehouseId,
                    'zone_code' => $zoneCode,
                    'zone_type' => $zoneType,
                ],
                userId: $userId,
                tenantId: $tenantId
            );

            return $zoneId;
        });
    }

    /**
     * Create location within zone
     *
     * @param  int  $zoneId  Zone ID
     * @param  string  $locationCode  Location code (e.g., 'A-01-01-01')
     * @param  string  $locationType  Location type (shelf, bin, pallet, floor)
     * @param  int  $capacity  Capacity in units
     * @param  string  $capacityUnit  Capacity unit (kg, pcs, m3)
     * @param  int  $userId  User ID
     * @param  int  $tenantId  Tenant ID
     * @return int Location ID
     */
    public function createLocation(
        int $zoneId,
        string $locationCode,
        string $locationType,
        int $capacity,
        string $capacityUnit,
        int $userId,
        int $tenantId
    ): int {
        $correlationId = Str::uuid()->toString();

        return $this->db->transaction(function () use (
            $zoneId,
            $locationCode,
            $locationType,
            $capacity,
            $capacityUnit,
            $userId,
            $tenantId,
            $correlationId
        ) {
            $zone = $this->db->table('warehouse_zones')
                ->where('id', $zoneId)
                ->first();

            if (! $zone) {
                throw new \RuntimeException("Zone {$zoneId} not found");
            }

            $existing = $this->db->table('warehouse_locations')
                ->where('zone_id', $zoneId)
                ->where('code', $locationCode)
                ->first();

            if ($existing) {
                throw new \RuntimeException("Location {$locationCode} already exists in zone");
            }

            $locationId = $this->db->table('warehouse_locations')->insertGetId([
                'uuid' => Str::uuid()->toString(),
                'zone_id' => $zoneId,
                'warehouse_id' => $zone->warehouse_id,
                'code' => $locationCode,
                'type' => $locationType,
                'capacity' => $capacity,
                'capacity_unit' => $capacityUnit,
                'current_usage' => 0,
                'tenant_id' => $tenantId,
                'is_active' => true,
                'created_by' => $userId,
                'created_at' => now(),
            ]);

            $this->logCreated(
                entityType: 'WarehouseLocation',
                entityId: $locationId,
                context: [
                    'correlation_id' => $correlationId,
                    'zone_id' => $zoneId,
                    'location_code' => $locationCode,
                    'location_type' => $locationType,
                    'capacity' => $capacity,
                ],
                userId: $userId,
                tenantId: $tenantId
            );

            return $locationId;
        });
    }

    /**
     * Assign item to location
     *
     * @param  int  $locationId  Location ID
     * @param  int  $inventoryItemId  Inventory item ID
     * @param  int  $quantity  Quantity
     * @param  int  $userId  User ID
     * @param  int  $tenantId  Tenant ID
     * @return bool Success
     */
    public function assignItemToLocation(
        int $locationId,
        int $inventoryItemId,
        int $quantity,
        int $userId,
        int $tenantId
    ): bool {
        $correlationId = Str::uuid()->toString();

        return $this->db->transaction(function () use (
            $locationId,
            $inventoryItemId,
            $quantity,
            $userId,
            $tenantId,
            $correlationId
        ) {
            $location = $this->db->table('warehouse_locations')
                ->where('id', $locationId)
                ->lockForUpdate()
                ->first();

            if (! $location) {
                throw new \RuntimeException("Location {$locationId} not found");
            }

            $newUsage = $location->current_usage + $quantity;

            if ($newUsage > $location->capacity) {
                throw new \RuntimeException("Location capacity exceeded. Capacity: {$location->capacity}, Usage: {$newUsage}");
            }

            $this->db->table('warehouse_locations')
                ->where('id', $locationId)
                ->update([
                    'current_usage' => $newUsage,
                    'updated_at' => now(),
                ]);

            $existingAssignment = $this->db->table('location_items')
                ->where('location_id', $locationId)
                ->where('inventory_item_id', $inventoryItemId)
                ->first();

            if ($existingAssignment) {
                $this->db->table('location_items')
                    ->where('id', $existingAssignment->id)
                    ->increment('quantity', $quantity);
            } else {
                $this->db->table('location_items')->insert([
                    'location_id' => $locationId,
                    'inventory_item_id' => $inventoryItemId,
                    'quantity' => $quantity,
                    'tenant_id' => $tenantId,
                    'created_at' => now(),
                ]);
            }

            $this->logAction(
                action: 'item_assigned_to_location',
                entityType: 'WarehouseLocation',
                entityId: $locationId,
                context: [
                    'correlation_id' => $correlationId,
                    'inventory_item_id' => $inventoryItemId,
                    'quantity' => $quantity,
                    'new_usage' => $newUsage,
                    'capacity' => $location->capacity,
                ],
                userId: $userId,
                tenantId: $tenantId
            );

            return true;
        });
    }

    /**
     * Get location utilization report
     *
     * @param  int  $warehouseId  Warehouse ID
     * @return array Utilization data
     */
    public function getLocationUtilization(int $warehouseId): array
    {
        $locations = $this->db->table('warehouse_locations')
            ->where('warehouse_id', $warehouseId)
            ->where('is_active', true)
            ->get();

        $totalCapacity = $locations->sum('capacity');
        $totalUsage = $locations->sum('current_usage');
        $utilizationRate = $totalCapacity > 0 ? ($totalUsage / $totalCapacity) * 100 : 0;

        $zoneUtilization = $locations->groupBy('zone_id')->map(function ($zoneLocations) {
            $zoneCapacity = $zoneLocations->sum('capacity');
            $zoneUsage = $zoneLocations->sum('current_usage');

            return [
                'capacity' => $zoneCapacity,
                'usage' => $zoneUsage,
                'utilization_rate' => $zoneCapacity > 0 ? ($zoneUsage / $zoneCapacity) * 100 : 0,
                'location_count' => $zoneLocations->count(),
            ];
        });

        $overCapacityLocations = $locations
            ->filter(fn ($loc) => $loc->current_usage > $loc->capacity)
            ->values();

        $underutilizedLocations = $locations
            ->filter(fn ($loc) => $loc->current_usage < ($loc->capacity * 0.3))
            ->values();

        return [
            'warehouse_id' => $warehouseId,
            'total_locations' => $locations->count(),
            'total_capacity' => $totalCapacity,
            'total_usage' => $totalUsage,
            'utilization_rate' => round($utilizationRate, 2),
            'zone_utilization' => $zoneUtilization,
            'over_capacity_count' => $overCapacityLocations->count(),
            'underutilized_count' => $underutilizedLocations->count(),
            'over_capacity_locations' => $overCapacityLocations->pluck('code')->toArray(),
            'underutilized_locations' => $underutilizedLocations->pluck('code')->toArray(),
        ];
    }

    /**
     * Get optimal location for new item placement
     *
     * @param  int  $warehouseId  Warehouse ID
     * @param  string  $itemType  Item type (fast_mover, slow_mover, bulky, fragile)
     * @param  int  $requiredCapacity  Required capacity
     * @return array|null Location recommendation
     */
    public function getOptimalLocation(
        int $warehouseId,
        string $itemType,
        int $requiredCapacity
    ): ?array {
        $zoneTypeMap = [
            'fast_mover' => 'picking',
            'slow_mover' => 'storage',
            'bulky' => 'floor',
            'fragile' => 'shelf',
        ];

        $targetZoneType = $zoneTypeMap[$itemType] ?? 'storage';

        $locations = $this->db->table('warehouse_locations as l')
            ->join('warehouse_zones as z', 'l.zone_id', '=', 'z.id')
            ->where('l.warehouse_id', $warehouseId)
            ->where('z.type', $targetZoneType)
            ->where('l.is_active', true)
            ->whereRaw('l.capacity - l.current_usage >= ?', [$requiredCapacity])
            ->select('l.*', 'z.type as zone_type')
            ->orderByRaw('(l.capacity - l.current_usage) ASC')
            ->limit(10)
            ->get();

        if ($locations->isEmpty()) {
            return null;
        }

        $bestLocation = $locations->first();

        return [
            'location_id' => $bestLocation->id,
            'location_code' => $bestLocation->code,
            'zone_type' => $bestLocation->zone_type,
            'available_capacity' => $bestLocation->capacity - $bestLocation->current_usage,
            'utilization_rate' => ($bestLocation->current_usage / $bestLocation->capacity) * 100,
        ];
    }

    /**
     * Deactivate location
     *
     * @param  int  $locationId  Location ID
     * @param  int  $userId  User ID
     * @param  int  $tenantId  Tenant ID
     * @return bool Success
     */
    public function deactivateLocation(int $locationId, int $userId, int $tenantId): bool
    {
        $correlationId = Str::uuid()->toString();

        return $this->db->transaction(function () use ($locationId, $userId, $tenantId, $correlationId) {
            $location = $this->db->table('warehouse_locations')
                ->where('id', $locationId)
                ->first();

            if (! $location) {
                throw new \RuntimeException("Location {$locationId} not found");
            }

            $itemCount = $this->db->table('location_items')
                ->where('location_id', $locationId)
                ->count();

            if ($itemCount > 0) {
                throw new \RuntimeException("Cannot deactivate location with {$itemCount} items. Move items first.");
            }

            $this->db->table('warehouse_locations')
                ->where('id', $locationId)
                ->update([
                    'is_active' => false,
                    'updated_at' => now(),
                ]);

            $this->logAction(
                action: 'location_deactivated',
                entityType: 'WarehouseLocation',
                entityId: $locationId,
                context: [
                    'correlation_id' => $correlationId,
                    'location_code' => $location->code,
                ],
                userId: $userId,
                tenantId: $tenantId
            );

            return true;
        });
    }

    /**
     * Get location hierarchy
     *
     * @param  int  $warehouseId  Warehouse ID
     * @return array Hierarchy
     */
    public function getLocationHierarchy(int $warehouseId): array
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

            return [
                'zone_id' => $zone->id,
                'zone_code' => $zone->code,
                'zone_name' => $zone->name,
                'zone_type' => $zone->type,
                'location_count' => $locations->count(),
                'locations' => $locations->map(fn ($loc) => [
                    'id' => $loc->id,
                    'code' => $loc->code,
                    'type' => $loc->type,
                    'capacity' => $loc->capacity,
                    'current_usage' => $loc->current_usage,
                    'utilization_rate' => $loc->capacity > 0 ? ($loc->current_usage / $loc->capacity) * 100 : 0,
                ])->toArray(),
            ];
        })->toArray();
    }
}
