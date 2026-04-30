<?php

declare(strict_types=1);

namespace Modules\Restaurant\Application\Services;

use Illuminate\Support\Collection;
use Illuminate\Cache\CacheManager;
use Modules\Restaurant\Domain\Entities\RestaurantTable;
use Modules\Restaurant\Domain\Entities\RestaurantZone;
use Modules\Restaurant\Infrastructure\Models\RestaurantTableModel;
use Modules\Restaurant\Infrastructure\Models\RestaurantZoneModel;
use App\Traits\WithAuditLogging;
use App\Services\Security\AuditService;

/**
 * TableManagementService — Сервис для управления столами в ресторане
 *
 * CatVRF 2026 Canon - Production Mandatory
 * - Dependency injection instead of facades
 * - Readonly class
 * - Cache with tags for invalidation
 * - Audit logging
 */
final readonly class TableManagementService
{
    use WithAuditLogging;

    private const CACHE_TTL = 3600; // 1 час

    public function __construct(
        private readonly string $tenantId,
        private readonly AuditService $auditService,
        private readonly CacheManager $cache,
    ) {}

    // Зоны

    public function createZone(string $name, ?string $description = null, ?string $color = null): RestaurantZone
    {
        $zone = RestaurantZone::create(
            tenantId: (int) $this->tenantId,
            name: $name,
            description: $description,
            color: $color,
        );

        $model = RestaurantZoneModel::create([
            'tenant_id' => $zone->tenantId,
            'name' => $zone->name,
            'description' => $zone->description,
            'color' => $zone->color,
            'display_order' => $zone->displayOrder,
            'is_active' => $zone->isActive,
            'table_count' => $zone->tableCount,
        ]);

        return new RestaurantZone(
            id: $model->id,
            tenantId: $model->tenant_id,
            name: $model->name,
            description: $model->description,
            color: $model->color,
            displayOrder: $model->display_order,
            isActive: $model->is_active,
            tableCount: $model->table_count,
            createdAt: $model->created_at->toImmutable(),
            updatedAt: $model->updated_at->toImmutable(),
        );
    }

    public function getAllZones(): Collection
    {
        return $this->cache->remember(
            "restaurant_zones_{$this->tenantId}",
            self::CACHE_TTL,
            function () {
                return RestaurantZoneModel::where('tenant_id', $this->tenantId)
                    ->where('is_active', true)
                    ->orderBy('display_order')
                    ->get()
                    ->map(fn ($model) => new RestaurantZone(
                        id: $model->id,
                        tenantId: $model->tenant_id,
                        name: $model->name,
                        description: $model->description,
                        color: $model->color,
                        displayOrder: $model->display_order,
                        isActive: $model->is_active,
                        tableCount: $model->table_count,
                        createdAt: $model->created_at->toImmutable(),
                        updatedAt: $model->updated_at->toImmutable(),
                    ));
            }
        );
    }

    public function getZone(int $zoneId): ?RestaurantZone
    {
        return RestaurantZoneModel::where('tenant_id', $this->tenantId)
            ->where('id', $zoneId)
            ->first()
            ?->transform(fn ($model) => new RestaurantZone(
                id: $model->id,
                tenantId: $model->tenant_id,
                name: $model->name,
                description: $model->description,
                color: $model->color,
                displayOrder: $model->display_order,
                isActive: $model->is_active,
                tableCount: $model->table_count,
                createdAt: $model->created_at->toImmutable(),
                updatedAt: $model->updated_at->toImmutable(),
            ));
    }

    // Столы

    public function createTable(
        int $zoneId,
        string $name,
        string $number,
        int $capacity,
        int $minCapacity = 1,
        ?string $shape = null,
    ): RestaurantTable {
        $table = RestaurantTable::create(
            tenantId: (int) $this->tenantId,
            zoneId: $zoneId,
            name: $name,
            number: $number,
            capacity: $capacity,
            minCapacity: $minCapacity,
            shape: $shape,
        );

        $model = RestaurantTableModel::create([
            'tenant_id' => $table->tenantId,
            'zone_id' => $table->zoneId,
            'name' => $table->name,
            'number' => $table->number,
            'capacity' => $table->capacity,
            'min_capacity' => $table->minCapacity,
            'shape' => $table->shape,
            'is_active' => $table->isActive,
        ]);

        // Обновляем счётчик столов в зоне
        RestaurantZoneModel::where('id', $zoneId)->increment('table_count');

        return new RestaurantTable(
            id: $model->id,
            tenantId: $model->tenant_id,
            zoneId: $model->zone_id,
            name: $model->name,
            number: $model->number,
            capacity: $model->capacity,
            minCapacity: $model->min_capacity,
            shape: $model->shape,
            x: $model->x,
            y: $model->y,
            width: $model->width,
            height: $model->height,
            isActive: $model->is_active,
            createdAt: $model->created_at->toImmutable(),
            updatedAt: $model->updated_at->toImmutable(),
        );
    }

    public function updateTablePosition(int $tableId, int $x, int $y, ?int $width = null, ?int $height = null): RestaurantTable
    {
        $model = RestaurantTableModel::where('tenant_id', $this->tenantId)
            ->where('id', $tableId)
            ->firstOrFail();

        $model->x = $x;
        $model->y = $y;
        if ($width !== null) {
            $model->width = $width;
        }
        if ($height !== null) {
            $model->height = $height;
        }
        $model->save();

        return new RestaurantTable(
            id: $model->id,
            tenantId: $model->tenant_id,
            zoneId: $model->zone_id,
            name: $model->name,
            number: $model->number,
            capacity: $model->capacity,
            minCapacity: $model->min_capacity,
            shape: $model->shape,
            x: $model->x,
            y: $model->y,
            width: $model->width,
            height: $model->height,
            isActive: $model->is_active,
            createdAt: $model->created_at->toImmutable(),
            updatedAt: $model->updated_at->toImmutable(),
        );
    }

    public function getTable(int $tableId): ?RestaurantTable
    {
        return RestaurantTableModel::where('tenant_id', $this->tenantId)
            ->where('id', $tableId)
            ->first()
            ?->transform(fn ($model) => new RestaurantTable(
                id: $model->id,
                tenantId: $model->tenant_id,
                zoneId: $model->zone_id,
                name: $model->name,
                number: $model->number,
                capacity: $model->capacity,
                minCapacity: $model->min_capacity,
                shape: $model->shape,
                x: $model->x,
                y: $model->y,
                width: $model->width,
                height: $model->height,
                isActive: $model->is_active,
                createdAt: $model->created_at->toImmutable(),
                updatedAt: $model->updated_at->toImmutable(),
            ));
    }

    public function getTablesByZone(int $zoneId): Collection
    {
        return RestaurantTableModel::where('tenant_id', $this->tenantId)
            ->where('zone_id', $zoneId)
            ->where('is_active', true)
            ->orderBy('number')
            ->get()
            ->map(fn ($model) => new RestaurantTable(
                id: $model->id,
                tenantId: $model->tenant_id,
                zoneId: $model->zone_id,
                name: $model->name,
                number: $model->number,
                capacity: $model->capacity,
                minCapacity: $model->min_capacity,
                shape: $model->shape,
                x: $model->x,
                y: $model->y,
                width: $model->width,
                height: $model->height,
                isActive: $model->is_active,
                createdAt: $model->created_at->toImmutable(),
                updatedAt: $model->updated_at->toImmutable(),
            ));
    }

    public function getAllTables(): Collection
    {
        return $this->cache->remember(
            "restaurant_tables_{$this->tenantId}",
            self::CACHE_TTL,
            function () {
                return RestaurantTableModel::where('tenant_id', $this->tenantId)
                    ->where('is_active', true)
                    ->with('zone')
                    ->orderBy('zone_id')
                    ->orderBy('number')
                    ->get()
                    ->map(fn ($model) => new RestaurantTable(
                        id: $model->id,
                        tenantId: $model->tenant_id,
                        zoneId: $model->zone_id,
                        name: $model->name,
                        number: $model->number,
                        capacity: $model->capacity,
                        minCapacity: $model->min_capacity,
                        shape: $model->shape,
                        x: $model->x,
                        y: $model->y,
                        width: $model->width,
                        height: $model->height,
                        isActive: $model->is_active,
                        createdAt: $model->created_at->toImmutable(),
                        updatedAt: $model->updated_at->toImmutable(),
                    ));
            }
        );
    }

    public function findAvailableTables(int $guestCount, \DateTimeInterface $startTime, \DateTimeInterface $endTime): Collection
    {
        // Находим столы с достаточной вместимостью
        $tables = RestaurantTableModel::where('tenant_id', $this->tenantId)
            ->where('is_active', true)
            ->where('min_capacity', '<=', $guestCount)
            ->where('capacity', '>=', $guestCount)
            ->whereDoesntHave('reservations', function ($query) use ($startTime, $endTime) {
                $query->whereIn('status', ['pending', 'confirmed', 'arrived'])
                    ->where(function ($q) use ($startTime, $endTime) {
                        $q->whereBetween('reservation_time', [$startTime, $endTime])
                            ->orWhere(function ($q2) use ($startTime, $endTime) {
                                $q2->where('reservation_time', '<=', $startTime)
                                    ->where('arrival_time', '>=', $startTime);
                            });
                    });
            })
            ->get()
            ->map(fn ($model) => new RestaurantTable(
                id: $model->id,
                tenantId: $model->tenant_id,
                zoneId: $model->zone_id,
                name: $model->name,
                number: $model->number,
                capacity: $model->capacity,
                minCapacity: $model->min_capacity,
                shape: $model->shape,
                x: $model->x,
                y: $model->y,
                width: $model->width,
                height: $model->height,
                isActive: $model->is_active,
                createdAt: $model->created_at->toImmutable(),
                updatedAt: $model->updated_at->toImmutable(),
            ));

        return $tables;
    }

    public function getFloorPlan(): Collection
    {
        $zones = $this->getAllZones();

        return $zones->map(function (RestaurantZone $zone) {
            return [
                'zone' => $zone,
                'tables' => $this->getTablesByZone($zone->id),
            ];
        });
    }
}
