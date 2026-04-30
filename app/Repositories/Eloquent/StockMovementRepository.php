<?php

declare(strict_types=1);

namespace App\Repositories\Eloquent;

use App\Models\WarehouseStockMovement;
use App\Repositories\Contracts\StockMovementRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

/**
 * Stock Movement Repository Implementation
 *
 * Eloquent implementation of stock movement data access operations.
 */
final readonly class StockMovementRepository implements StockMovementRepositoryInterface
{
    public function findById(int $id): ?WarehouseStockMovement
    {
        return WarehouseStockMovement::find($id);
    }

    public function getForWarehouse(int $warehouseId, array $filters = []): LengthAwarePaginator
    {
        $query = WarehouseStockMovement::where('warehouse_id', $warehouseId);

        if (isset($filters['movement_type'])) {
            $query->where('movement_type', $filters['movement_type']);
        }

        if (isset($filters['start_date']) && isset($filters['end_date'])) {
            $query->whereBetween('created_at', [$filters['start_date'], $filters['end_date']]);
        }

        return $query->with(['fromZone', 'toZone', 'inventoryItem'])
            ->orderBy('created_at', 'desc')
            ->paginate($filters['per_page'] ?? 20);
    }

    public function getForInventoryItem(int $itemId, array $filters = []): LengthAwarePaginator
    {
        $query = WarehouseStockMovement::where('inventory_item_id', $itemId);

        if (isset($filters['movement_type'])) {
            $query->where('movement_type', $filters['movement_type']);
        }

        return $query->orderBy('created_at', 'desc')
            ->paginate($filters['per_page'] ?? 20);
    }

    public function getByType(string $movementType, array $filters = []): LengthAwarePaginator
    {
        $query = WarehouseStockMovement::where('movement_type', $movementType);

        if (isset($filters['warehouse_id'])) {
            $query->where('warehouse_id', $filters['warehouse_id']);
        }

        return $query->orderBy('created_at', 'desc')
            ->paginate($filters['per_page'] ?? 20);
    }

    public function getForOrder(string $orderId): array
    {
        return WarehouseStockMovement::where('order_id', $orderId)
            ->with(['warehouse', 'fromZone', 'toZone'])
            ->orderBy('created_at', 'asc')
            ->get()
            ->toArray();
    }

    public function create(array $data): WarehouseStockMovement
    {
        return WarehouseStockMovement::create($data);
    }

    public function getDateRange(string $startDate, string $endDate, array $filters = []): LengthAwarePaginator
    {
        $query = WarehouseStockMovement::whereBetween('created_at', [$startDate, $endDate]);

        if (isset($filters['warehouse_id'])) {
            $query->where('warehouse_id', $filters['warehouse_id']);
        }

        if (isset($filters['movement_type'])) {
            $query->where('movement_type', $filters['movement_type']);
        }

        return $query->orderBy('created_at', 'desc')
            ->paginate($filters['per_page'] ?? 20);
    }

    public function getStatistics(int $warehouseId, string $startDate, string $endDate): array
    {
        $movements = WarehouseStockMovement::where('warehouse_id', $warehouseId)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->get();

        return [
            'total_movements' => $movements->count(),
            'inbound' => $movements->where('movement_type', 'receipt')->count(),
            'outbound' => $movements->whereIn('movement_type', ['shipment', 'picking'])->count(),
            'transfers' => $movements->where('movement_type', 'transfer')->count(),
            'adjustments' => $movements->where('movement_type', 'adjustment')->count(),
            'total_quantity_moved' => $movements->sum('quantity'),
        ];
    }
}
