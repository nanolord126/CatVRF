<?php

namespace App\Policies;

use App\Models\User;
use App\Models\B2B\InventoryItem;
use Illuminate\Auth\Access\HandlesAuthorization;

class InventoryItemPolicy extends BaseSecurityPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'tenant-owner', 'manager', 'staff']);
    }

    public function view(User $user, InventoryItem $item): bool
    {
        return $user->hasAnyRole(['admin', 'tenant-owner', 'manager', 'staff']);
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'tenant-owner', 'manager']);
    }

    public function update(User $user, InventoryItem $item): bool
    {
        return $user->hasAnyRole(['admin', 'tenant-owner', 'manager']);
    }

    public function delete(User $user, InventoryItem $item): bool
    {
        return $user->hasRole('tenant-owner');
    }
}

