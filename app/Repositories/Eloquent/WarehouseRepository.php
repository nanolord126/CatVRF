<?php

declare(strict_types=1);

namespace App\Repositories\Eloquent;

use App\Domains\Logistics\WarehouseRentals\Models\Warehouse;
use App\Repositories\Contracts\WarehouseRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

/**
 * Warehouse Repository Implementation
 *
 * Eloquent implementation of warehouse data access operations.
 */
final readonly class WarehouseRepository implements WarehouseRepositoryInterface
{
    public function findById(int $id): ?Warehouse
    {
        return Warehouse::find($id);
    }

    public function findByUuid(string $uuid): ?Warehouse
    {
        return Warehouse::where('uuid', $uuid)->first();
    }

    public function getAllForTenant(int $tenantId, array $filters = []): LengthAwarePaginator
    {
        $query = Warehouse::where('tenant_id', $tenantId);

        if (isset($filters['is_active'])) {
            $query->where('is_active', $filters['is_active']);
        }

        if (isset($filters['is_verified'])) {
            $query->where('is_verified', $filters['is_verified']);
        }

        if (isset($filters['search'])) {
            $query->where('name', 'like', '%' . $filters['search'] . '%');
        }

        return $query->orderBy('created_at', 'desc')
            ->paginate($filters['per_page'] ?? 20);
    }

    public function getActiveForTenant(int $tenantId): array
    {
        return Warehouse::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->get()
            ->toArray();
    }

    public function create(array $data): Warehouse
    {
        return Warehouse::create($data);
    }

    public function update(int $id, array $data): bool
    {
        $warehouse = $this->findById($id);
        
        if (!$warehouse) {
            return false;
        }

        return $warehouse->update($data);
    }

    public function delete(int $id): bool
    {
        $warehouse = $this->findById($id);
        
        if (!$warehouse) {
            return false;
        }

        return $warehouse->delete();
    }

    public function hasActiveLicense(int $warehouseId): bool
    {
        return DB::table('warehouse_licenses')
            ->where('warehouse_id', $warehouseId)
            ->where('status', 'active')
            ->where('expiry_date', '>', now())
            ->exists();
    }

    public function getUtilization(int $warehouseId): float
    {
        $warehouse = $this->findById($id);
        
        if (!$warehouse || $warehouse->capacity === 0) {
            return 0.0;
        }

        return ($warehouse->current_stock / $warehouse->capacity) * 100;
    }
}
