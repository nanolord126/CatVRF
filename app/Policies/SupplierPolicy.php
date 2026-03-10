<?php

namespace App\Policies;

use App\Models\User;
use App\Models\B2B\Supplier;
use Illuminate\Auth\Access\HandlesAuthorization;

class SupplierPolicy extends BaseSecurityPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'tenant-owner', 'manager']);
    }

    public function view(User $user, Supplier $supplier): bool
    {
        return $user->hasAnyRole(['admin', 'tenant-owner', 'manager']);
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'tenant-owner']);
    }

    public function update(User $user, Supplier $supplier): bool
    {
        return $user->hasAnyRole(['admin', 'tenant-owner']);
    }

    public function delete(User $user, Supplier $supplier): bool
    {
        return $user->hasRole('tenant-owner');
    }
}

