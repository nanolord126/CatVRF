<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\Models\WarehouseStockMovement;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Stock Movement Repository Interface
 *
 * Contract for stock movement data access operations.
 * Follows Repository pattern for Clean Architecture.
 */
interface StockMovementRepositoryInterface
{
    /**
     * Find stock movement by ID
     */
    public function findById(int $id): ?WarehouseStockMovement;

    /**
     * Get stock movements for a warehouse
     */
    public function getForWarehouse(int $warehouseId, array $filters = []): LengthAwarePaginator;

    /**
     * Get stock movements for an inventory item
     */
    public function getForInventoryItem(int $itemId, array $filters = []): LengthAwarePaginator;

    /**
     * Get stock movements by type
     */
    public function getByType(string $movementType, array $filters = []): LengthAwarePaginator;

    /**
     * Get stock movements for a specific order
     */
    public function getForOrder(string $orderId): array;

    /**
     * Create new stock movement
     */
    public function create(array $data): WarehouseStockMovement;

    /**
     * Get stock movements for date range
     */
    public function getDateRange(string $startDate, string $endDate, array $filters = []): LengthAwarePaginator;

    /**
     * Get movement statistics for a warehouse
     */
    public function getStatistics(int $warehouseId, string $startDate, string $endDate): array;
}
