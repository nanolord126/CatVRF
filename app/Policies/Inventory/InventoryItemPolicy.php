<?php

declare(strict_types=1);

namespace App\Policies\Inventory;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

/**
 * Inventory Item Policy
 *
 * Role-based access control for inventory items:
 * - View permissions
 * - Create permissions
 * - Update permissions
 * - Delete permissions
 * - Audit trail access
 *
 * @author CatVRF Team
 * @version 2026.04.28
 */
class InventoryItemPolicy
{
    use HandlesAuthorization;

    /**
     * Determine if user can view inventory items
     *
     * @param  User  $user
     * @return bool
     */
    public function viewAny(User $user): bool
    {
        return $user->hasRole('admin')
            || $user->hasRole('warehouse_manager')
            || $user->hasRole('inventory_manager')
            || $user->hasRole('warehouse_worker');
    }

    /**
     * Determine if user can view specific inventory item
     *
     * @param  User  $user
     * @param  int  $inventoryItemId
     * @return bool
     */
    public function view(User $user, int $inventoryItemId): bool
    {
        if (! $this->viewAny($user)) {
            return false;
        }

        if ($user->hasRole('admin') || $user->hasRole('warehouse_manager')) {
            return true;
        }

        $item = \DB::table('inventory_items')->where('id', $inventoryItemId)->first();

        if (! $item) {
            return false;
        }

        if ($item->tenant_id !== $user->tenant_id) {
            return false;
        }

        if ($user->hasRole('inventory_manager') || $user->hasRole('warehouse_worker')) {
            return $item->warehouse_id === $user->warehouse_id;
        }

        return false;
    }

    /**
     * Determine if user can create inventory items
     *
     * @param  User  $user
     * @return bool
     */
    public function create(User $user): bool
    {
        return $user->hasRole('admin')
            || $user->hasRole('warehouse_manager')
            || $user->hasRole('inventory_manager');
    }

    /**
     * Determine if user can update inventory items
     *
     * @param  User  $user
     * @param  int  $inventoryItemId
     * @return bool
     */
    public function update(User $user, int $inventoryItemId): bool
    {
        if (! $this->create($user)) {
            return false;
        }

        if ($user->hasRole('admin')) {
            return true;
        }

        $item = \DB::table('inventory_items')->where('id', $inventoryItemId)->first();

        if (! $item) {
            return false;
        }

        if ($item->tenant_id !== $user->tenant_id) {
            return false;
        }

        if ($user->hasRole('warehouse_manager')) {
            return true;
        }

        if ($user->hasRole('inventory_manager')) {
            return $item->warehouse_id === $user->warehouse_id;
        }

        return false;
    }

    /**
     * Determine if user can delete inventory items
     *
     * @param  User  $user
     * @param  int  $inventoryItemId
     * @return bool
     */
    public function delete(User $user, int $inventoryItemId): bool
    {
        return $user->hasRole('admin');
    }

    /**
     * Determine if user can adjust inventory (stock in/out)
     *
     * @param  User  $user
     * @param  int  $inventoryItemId
     * @return bool
     */
    public function adjust(User $user, int $inventoryItemId): bool
    {
        if (! $this->view($user, $inventoryItemId)) {
            return false;
        }

        return $user->hasRole('admin')
            || $user->hasRole('warehouse_manager')
            || $user->hasRole('inventory_manager');
    }

    /**
     * Determine if user can view audit trail
     *
     * @param  User  $user
     * @return bool
     */
    public function viewAuditTrail(User $user): bool
    {
        return $user->hasRole('admin')
            || $user->hasRole('warehouse_manager')
            || $user->hasRole('compliance_officer');
    }

    /**
     * Determine if user can export inventory data
     *
     * @param  User  $user
     * @return bool
     */
    public function export(User $user): bool
    {
        return $user->hasRole('admin')
            || $user->hasRole('warehouse_manager')
            || $user->hasRole('inventory_manager');
    }

    /**
     * Determine if user can perform cycle counting
     *
     * @param  User  $user
     * @return bool
     */
    public function cycleCount(User $user): bool
    {
        return $user->hasRole('admin')
            || $user->hasRole('warehouse_manager')
            || $user->hasRole('inventory_manager');
    }

    /**
     * Determine if user can approve cycle counts
     *
     * @param  User  $user
     * @return bool
     */
    public function approveCycleCount(User $user): bool
    {
        return $user->hasRole('admin')
            || $user->hasRole('warehouse_manager');
    }
}
