<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class DomainPolicy extends BaseSecurityPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'tenant-owner', 'manager']);
    }

    public function view(User $user, $model): bool
    {
        return $user->hasAnyRole(['admin', 'tenant-owner', 'manager']);
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'tenant-owner']);
    }

    public function update(User $user, $model): bool
    {
        return $user->hasAnyRole(['admin', 'tenant-owner']);
    }

    public function delete(User $user, $model): bool
    {
        return $user->hasRole('admin');
    }

    public function restore(User $user, $model): bool
    {
        return $user->hasRole('admin');
    }

    public function forceDelete(User $user, $model): bool
    {
        return $user->hasRole('admin');
    }
}

