<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\Domains\Logistics\WarehouseRentals\Models\Warehouse;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Warehouse Repository Interface
 *
 * Contract for warehouse data access operations.
 * Follows Repository pattern for Clean Architecture.
 */
interface WarehouseRepositoryInterface
{
    /**
     * Find warehouse by ID
     */
    public function findById(int $id): ?Warehouse;

    /**
     * Find warehouse by UUID
     */
    public function findByUuid(string $uuid): ?Warehouse;

    /**
     * Get all warehouses for a tenant
     */
    public function getAllForTenant(int $tenantId, array $filters = []): LengthAwarePaginator;

    /**
     * Get active warehouses for a tenant
     */
    public function getActiveForTenant(int $tenantId): array;

    /**
     * Create new warehouse
     */
    public function create(array $data): Warehouse;

    /**
     * Update warehouse
     */
    public function update(int $id, array $data): bool;

    /**
     * Delete warehouse (soft delete)
     */
    public function delete(int $id): bool;

    /**
     * Check if warehouse has active license
     */
    public function hasActiveLicense(int $warehouseId): bool;

    /**
     * Get warehouse utilization percentage
     */
    public function getUtilization(int $warehouseId): float;
}
