<?php

declare(strict_types=1);

namespace App\Domains\Inventory\Policies;

use App\Domains\Inventory\Models\InventoryCheck;

/**
 * Policy для InventoryCheck.
 *
 * Проверяет принадлежность к tenant.
 */
final class InventoryCheckPolicy
{
    public function viewAny(object $user): bool
    {
        return $user->tenant_id !== null;
    }

    public function view(object $user, InventoryCheck $check): bool
    {
        return $user->tenant_id === $check->tenant_id;
    }

    public function create(object $user): bool
    {
        return $user->tenant_id !== null;
    }

    public function update(object $user, InventoryCheck $check): bool
    {
        return $user->tenant_id === $check->tenant_id;
    }

    public function delete(object $user, InventoryCheck $check): bool
    {
        return $user->tenant_id === $check->tenant_id;
    }

    public function restore(object $user, InventoryCheck $check): bool
    {
        return $user->tenant_id === $check->tenant_id;
    }

    public function forceDelete(object $user, InventoryCheck $check): bool
    {
        return $user->tenant_id === $check->tenant_id;
    }
}
