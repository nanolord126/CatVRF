<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Services\RestaurantMenuItem;
use Illuminate\Auth\Access\HandlesAuthorization;

class RestaurantMenuItemPolicy extends BaseSecurityPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, RestaurantMenuItem $item): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'tenant-owner', 'manager']);
    }

    public function update(User $user, RestaurantMenuItem $item): bool
    {
        return $user->hasAnyRole(['admin', 'tenant-owner', 'manager']);
    }

    public function delete(User $user, RestaurantMenuItem $item): bool
    {
        return $user->hasAnyRole(['admin', 'tenant-owner']);
    }
}

