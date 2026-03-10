<?php

namespace App\Policies;

use App\Models\User;
use App\Models\HR\HRExchangeTask;
use Illuminate\Auth\Access\HandlesAuthorization;

class HRExchangeTaskPolicy extends BaseSecurityPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'tenant-owner', 'manager', 'staff']);
    }

    public function view(User $user, HRExchangeTask $task): bool
    {
        return $user->hasAnyRole(['admin', 'tenant-owner', 'manager', 'staff']);
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'tenant-owner', 'manager']);
    }

    public function update(User $user, HRExchangeTask $task): bool
    {
        return $user->hasAnyRole(['admin', 'tenant-owner', 'manager']);
    }

    public function delete(User $user, HRExchangeTask $task): bool
    {
        return $user->hasRole('tenant-owner');
    }
}

