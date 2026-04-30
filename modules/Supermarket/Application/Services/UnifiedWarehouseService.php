<?php

declare(strict_types=1);

namespace Modules\Supermarket\Application\Services;

use Modules\Supermarket\Domain\Entities\UnifiedWarehouse;
use Modules\Supermarket\Domain\Entities\UnifiedStock;
use Modules\Supermarket\Domain\Entities\InventoryReservation;
use Illuminate\Support\Facades\DB;

final class UnifiedWarehouseService
{
    public function __construct(
        private readonly string $correlationId,
    ) {}

    public function reserveForOrder(int $warehouseId, string $orderType, string $orderId, array $items): bool
    {
        $warehouse = UnifiedWarehouse::findOrFail($warehouseId);
        
        return DB::transaction(function () use ($warehouse, $orderType, $orderId, $items) {
            $warehouse->reserveStock($orderType, $items);

            foreach ($items as $item) {
                InventoryReservation::create([
                    'tenant_id' => $warehouse->tenant_id,
                    'warehouse_id' => $warehouse->id,
                    'order_id' => $orderId,
                    'order_type' => $orderType,
                    'sku' => $item['sku'],
                    'quantity' => $item['quantity'],
                    'status' => 'reserved',
                    'reserved_at' => now(),
                    'expires_at' => now()->addHours(24),
                    'correlation_id' => $this->correlationId,
                ]);
            }

            return true;
        });
    }

    public function releaseReservation(string $orderId, string $orderType): bool
    {
        $reservations = InventoryReservation::where('order_id', $orderId)
            ->where('order_type', $orderType)
            ->where('status', 'reserved')
            ->get();

        return DB::transaction(function () use ($reservations) {
            foreach ($reservations as $reservation) {
                $warehouse = $reservation->warehouse;
                $warehouse->releaseReservation($reservation->order_type, [
                    ['sku' => $reservation->sku, 'quantity' => $reservation->quantity],
                ]);
                $reservation->release();
            }

            return true;
        });
    }

    public function switchCapacityBetweenTypes(int $warehouseId, int $b2bToB2cAmount, int $b2cToB2bAmount): bool
    {
        $warehouse = UnifiedWarehouse::findOrFail($warehouseId);

        return DB::transaction(function () use ($warehouse, $b2bToB2cAmount, $b2cToB2bAmount) {
            if ($b2bToB2cAmount > 0) {
                $warehouse->switchCapacity('b2b', 'b2c', $b2bToB2cAmount);
            }

            if ($b2cToB2bAmount > 0) {
                $warehouse->switchCapacity('b2c', 'b2b', $b2cToB2bAmount);
            }

            return true;
        });
    }

    public function getWarehouseStats(int $warehouseId): array
    {
        $warehouse = UnifiedWarehouse::with('stock')->findOrFail($warehouseId);

        return [
            'total_capacity' => $warehouse->capacity,
            'capacity_used' => $warehouse->capacity_used,
            'capacity_percentage' => $warehouse->capacity > 0 
                ? round(($warehouse->capacity_used / $warehouse->capacity) * 100, 2) 
                : 0,
            'b2b' => [
                'capacity' => $warehouse->b2b_capacity,
                'used' => $warehouse->b2b_capacity_used,
                'percentage' => $warehouse->b2b_capacity > 0 
                    ? round(($warehouse->b2b_capacity_used / $warehouse->b2b_capacity) * 100, 2) 
                    : 0,
            ],
            'b2c' => [
                'capacity' => $warehouse->b2c_capacity,
                'used' => $warehouse->b2c_capacity_used,
                'percentage' => $warehouse->b2c_capacity > 0 
                    ? round(($warehouse->b2c_capacity_used / $warehouse->b2c_capacity) * 100, 2) 
                    : 0,
            ],
            'stock_value' => $warehouse->getTotalStockValue(),
            'low_stock_items' => $warehouse->stock->filter(fn($s) => $s->isLowStock())->count(),
            'overstocked_items' => $warehouse->stock->filter(fn($s) => $s->isOverstocked())->count(),
        ];
    }
}
