<?php

declare(strict_types=1);

namespace App\Repositories\Eloquent;

use App\Exceptions\OptimisticLockException;
use App\Models\InventoryItem;
use App\Repositories\Contracts\InventoryItemRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

/**
 * Inventory Item Repository Implementation
 *
 * Eloquent implementation of inventory item data access operations.
 */
final readonly class InventoryItemRepository implements InventoryItemRepositoryInterface
{
    public function findById(int $id): ?InventoryItem
    {
        return InventoryItem::find($id);
    }

    public function findBySku(string $sku): ?InventoryItem
    {
        return InventoryItem::where('sku', $sku)->first();
    }

    public function getAllForTenant(int $tenantId, array $filters = []): LengthAwarePaginator
    {
        $query = InventoryItem::where('tenant_id', $tenantId);

        if (isset($filters['sku'])) {
            $query->where('sku', 'like', '%' . $filters['sku'] . '%');
        }

        if (isset($filters['name'])) {
            $query->where('name', 'like', '%' . $filters['name'] . '%');
        }

        if (isset($filters['low_stock'])) {
            $query->whereColumn('current_stock', '<', 'min_stock_threshold');
        }

        return $query->orderBy('created_at', 'desc')
            ->paginate($filters['per_page'] ?? 20);
    }

    public function getLowStockItems(int $tenantId, int $threshold = 10): array
    {
        return InventoryItem::where('tenant_id', $tenantId)
            ->whereColumn('current_stock', '<', 'min_stock_threshold')
            ->where('min_stock_threshold', '<=', $threshold)
            ->get()
            ->toArray();
    }

    public function getExpiringSoonItems(int $tenantId, int $days = 30): array
    {
        // Get items with batches expiring soon
        return DB::table('warehouse_batches')
            ->join('warehouse_products', 'warehouse_batches.product_id', '=', 'warehouse_products.id')
            ->where('warehouse_batches.expiry_date', '<=', now()->addDays($days))
            ->where('warehouse_batches.expiry_date', '>', now())
            ->where('warehouse_batches.current_quantity', '>', 0)
            ->select('warehouse_products.*')
            ->distinct()
            ->get()
            ->toArray();
    }

    public function create(array $data): InventoryItem
    {
        return InventoryItem::create($data);
    }

    public function update(int $id, array $data, int $expectedVersion): bool
    {
        $item = $this->findById($id);
        
        if (!$item) {
            return false;
        }

        $currentVersion = $item->getOriginal('version') ?? $item->version;

        if ($currentVersion !== $expectedVersion) {
            throw new OptimisticLockException(
                entityType: 'inventory_item',
                entityId: $id,
                expectedVersion: $expectedVersion,
                actualVersion: $currentVersion
            );
        }

        return $item->update($data);
    }

    public function updateStock(int $id, int $quantity, int $expectedVersion): bool
    {
        return $this->update($id, ['current_stock' => $quantity], $expectedVersion);
    }

    public function delete(int $id): bool
    {
        $item = $this->findById($id);
        
        if (!$item) {
            return false;
        }

        return $item->delete();
    }

    public function reserveStock(int $itemId, int $quantity): bool
    {
        return DB::table('inventory_items')
            ->where('id', $itemId)
            ->where('current_stock', '>=', $quantity + DB::raw('hold_stock'))
            ->update([
                'hold_stock' => DB::raw('hold_stock + ' . $quantity),
                'updated_at' => now(),
            ]) > 0;
    }

    public function releaseReservedStock(int $itemId, int $quantity): bool
    {
        return DB::table('inventory_items')
            ->where('id', $itemId)
            ->where('hold_stock', '>=', $quantity)
            ->update([
                'hold_stock' => DB::raw('GREATEST(0, hold_stock - ' . $quantity . ')'),
                'current_stock' => DB::raw('current_stock + ' . $quantity),
                'updated_at' => now(),
            ]) > 0;
    }
}
