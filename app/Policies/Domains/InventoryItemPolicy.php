<?php declare(strict_types=1);

namespace App\Policies\Domains;

use App\Models\InventoryItem;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

final class InventoryItemPolicy
{
    use HandlesAuthorization;

    /**
     * Admins can do anything
     */
    public function before(User $user, string $ability): bool|null
    {
        if ($user->hasRole('admin')) {
            return true;
        }

        return null;
    }

    /**
     * View inventory item
     */
    public function view(User $user, InventoryItem $item): bool
    {
        // Tenant scoping
        if ($item->tenant_id !== $user->tenant_id) {
            return false;
        }

        // Business owner can view their own inventory
        if ($item->business_group_id === $user->current_business_group_id) {
            return $user->hasRole(['business_owner', 'manager', 'employee']);
        }

        return $user->hasRole(['manager', 'accountant']);
    }

    /**
     * Create inventory item
     */
    public function create(User $user): bool
    {
        return $user->hasRole(['business_owner', 'manager']);
    }

    /**
     * Update inventory (manager, business_owner)
     */
    public function update(User $user, InventoryItem $item): bool
    {
        if ($item->tenant_id !== $user->tenant_id) {
            return false;
        }

        if ($item->business_group_id === $user->current_business_group_id) {
            return $user->hasRole(['business_owner', 'manager']);
        }

        return $user->hasRole('manager');
    }

    /**
     * Adjust stock (manager, employee)
     */
    public function adjustStock(User $user, InventoryItem $item): bool
    {
        if ($item->tenant_id !== $user->tenant_id) {
            return false;
        }

        if ($item->business_group_id === $user->current_business_group_id) {
            return $user->hasRole(['manager', 'employee']);
        }

        return $user->hasRole('manager');
    }

    /**
     * Deduct stock (system/employee during operations)
     */
    public function deductStock(User $user, InventoryItem $item): bool
    {
        if ($item->tenant_id !== $user->tenant_id) {
            return false;
        }

        if ($item->business_group_id === $user->current_business_group_id) {
            return $user->hasRole(['employee', 'manager']);
        }

        return $user->hasRole('manager');
    }

    /**
     * View inventory history/logs
     */
    public function viewHistory(User $user, InventoryItem $item): bool
    {
        if ($item->tenant_id !== $user->tenant_id) {
            return false;
        }

        if ($item->business_group_id === $user->current_business_group_id) {
            return $user->hasRole(['business_owner', 'manager', 'accountant']);
        }

        return $user->hasRole(['manager', 'accountant']);
    }

    /**
     * Delete inventory item
     */
    public function delete(User $user, InventoryItem $item): bool
    {
        if ($item->tenant_id !== $user->tenant_id) {
            return false;
        }

        // Can only delete if no movements
        if ($item->current_stock !== 0) {
            return false;
        }

        if ($item->business_group_id === $user->current_business_group_id) {
            return $user->hasRole('business_owner');
        }

        return $user->hasRole('admin');
    }

    /**
     * Import inventory (bulk operations)
     */
    public function import(User $user): bool
    {
        return $user->hasRole(['business_owner', 'manager']);
    }

    /**
     * Export inventory
     */
    public function export(User $user): bool
    {
        return $user->hasRole(['business_owner', 'manager', 'accountant']);
    }
}
