<?php

declare(strict_types=1);

namespace Modules\Warehouse\Domain\Repositories;

use Modules\Warehouse\Domain\Entities\Batch;
use Modules\Warehouse\Domain\Entities\Bin;
use Modules\Warehouse\Domain\Entities\InventoryCount;
use Modules\Warehouse\Domain\Entities\InventoryItem;
use Modules\Warehouse\Domain\Entities\Product;
use Modules\Warehouse\Domain\Entities\StockMovement;
use Modules\Warehouse\Domain\Entities\Warehouse;
use Modules\Warehouse\Domain\Entities\WarehouseZone;
use Modules\Warehouse\Domain\ValueObjects\BatchId;
use Modules\Warehouse\Domain\ValueObjects\BinId;
use Modules\Warehouse\Domain\ValueObjects\InventoryCountId;
use Modules\Warehouse\Domain\ValueObjects\ProductId;
use Modules\Warehouse\Domain\ValueObjects\WarehouseId;
use Modules\Warehouse\Domain\ValueObjects\ZoneId;
use Modules\Warehouse\Domain\Enums\OrderTypeEnum;
use Modules\Warehouse\Domain\Enums\MovementTypeEnum;

/**
 * Repository Interfaces for Warehouse Domain
 *
 * All repository interfaces combined to avoid stub files
 *
 * @author CatVRF Team
 * @version 2026.04.28
 */

interface WarehouseRepositoryInterface
{
    public function save(Warehouse $warehouse): void;
    public function findById(WarehouseId $id): ?Warehouse;
    public function findByTenantId(string $tenantId): array;
    public function findActive(): array;
    public function findByBranchId(string $branchId): array;
    public function delete(WarehouseId $id): void;
    public function exists(WarehouseId $id): bool;
}

interface ZoneRepositoryInterface
{
    public function save(WarehouseZone $zone): void;
    public function findById(ZoneId $id): ?WarehouseZone;
    public function findByWarehouseId(WarehouseId $warehouseId): array;
    public function findActiveByWarehouseId(WarehouseId $warehouseId): array;
    public function delete(ZoneId $id): void;
    public function exists(ZoneId $id): bool;
}

interface BinRepositoryInterface
{
    public function save(Bin $bin): void;
    public function findById(BinId $id): ?Bin;
    public function findByZoneId(ZoneId $zoneId): array;
    public function findByCode(string $code): ?Bin;
    public function findActiveByZoneId(ZoneId $zoneId): array;
    public function delete(BinId $id): void;
    public function exists(BinId $id): bool;
}

interface InventoryItemRepositoryInterface
{
    public function save(InventoryItem $item): void;
    public function findById(string $id): ?InventoryItem;
    public function findByWarehouse(WarehouseId $warehouseId, ?string $branchId = null): array;
    public function findByWarehouseAndOrderType(WarehouseId $warehouseId, OrderTypeEnum $orderType, ?string $branchId = null): array;
    public function findSharedByWarehouse(WarehouseId $warehouseId, ?string $branchId = null): array;
    public function findByProductSku(string $sku, ?string $branchId = null): array;
    public function delete(string $id): void;
}

interface StockMovementRepositoryInterface
{
    public function save(StockMovement $movement): void;
    public function findById(string $id): ?StockMovement;
    public function findByWarehouse(WarehouseId $warehouseId, ?string $branchId = null): array;
    public function findByOrderType(OrderTypeEnum $orderType, ?string $branchId = null): array;
    public function findByMovementType(MovementTypeEnum $movementType, ?string $branchId = null): array;
    public function findByInventoryItem(string $inventoryItemId): array;
    public function findByDateRange(\DateTimeImmutable $from, \DateTimeImmutable $to, ?string $branchId = null): array;
}

interface BatchRepositoryInterface
{
    public function save(Batch $batch): void;
    public function findById(BatchId $id): ?Batch;
    public function findByBatchNumber(string $batchNumber): ?Batch;
    public function findByProductId(ProductId $productId): array;
    public function findByProductSku(string $productSku): array;
    public function findActiveByProductSku(string $productSku): array;
    public function findByWarehouseId(WarehouseId $warehouseId): array;
    public function findExpiringBatches(int $daysThreshold = 30): array;
    public function findExpiredBatches(): array;
    public function delete(BatchId $id): void;
    public function exists(BatchId $id): bool;
}

interface InventoryCountRepositoryInterface
{
    public function save(InventoryCount $inventoryCount): void;
    public function findById(InventoryCountId $id): ?InventoryCount;
    public function findByCountNumber(string $countNumber): ?InventoryCount;
    public function findByWarehouseId(WarehouseId $warehouseId): array;
    public function findScheduled(): array;
    public function findInProgress(): array;
    public function findPendingApproval(): array;
    public function delete(InventoryCountId $id): void;
    public function exists(InventoryCountId $id): bool;
}

interface ProductRepositoryInterface
{
    public function save(Product $product): void;
    public function findById(ProductId $id): ?Product;
    public function findBySku(string $sku): ?Product;
    public function findByBarcode(string $barcode): ?Product;
    public function findActive(): array;
    public function findByCategory(string $category): array;
    public function search(string $query): array;
    public function delete(ProductId $id): void;
    public function exists(ProductId $id): bool;
    public function skuExists(string $sku): bool;
}
