<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Services\TaxiRide;
use Illuminate\Auth\Access\HandlesAuthorization;

class TaxiRidePolicy extends BaseSecurityPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'tenant-owner', 'manager', 'dispatcher']);
    }

    public function view(User $user, TaxiRide $ride): bool
    {
        return $user->hasAnyRole(['admin', 'tenant-owner', 'manager', 'dispatcher']);
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'tenant-owner', 'manager', 'dispatcher']);
    }

    public function update(User $user, TaxiRide $ride): bool
    {
        return $user->hasAnyRole(['admin', 'tenant-owner', 'manager', 'dispatcher']);
    }

    public function delete(User $user, TaxiRide $ride): bool
    {
        return $user->hasRole('admin');
    }
}

