<?php

declare(strict_types=1);

namespace App\Domains\Taxi\Policies;

use App\Models\User;
use App\Domains\Taxi\Models\TaxiRide;
use Illuminate\Auth\Access\HandlesAuthorization;

final class TaxiRidePolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'tenant-owner', 'manager', 'dispatcher', 'driver']) &&
               $user->tenant_id !== null;
    }

    public function view(User $user, TaxiRide $ride): bool
    {
        if ($user->tenant_id !== $ride->tenant_id) {
            return false;
        }

        if ($user->hasAnyRole(['admin', 'tenant-owner', 'manager', 'dispatcher'])) {
            return true;
        }

        if ($user->hasRole('driver') && $ride->driver_id === $user->id) {
            return true;
        }

        return $ride->customer_id === $user->id;
    }

    public function create(User $user): bool
    {
        return $user->tenant_id !== null &&
               $user->hasAnyRole(['admin', 'tenant-owner', 'manager', 'dispatcher']);
    }

    public function update(User $user, TaxiRide $ride): bool
    {
        if ($user->tenant_id !== $ride->tenant_id) {
            return false;
        }

        if ($user->hasAnyRole(['admin', 'tenant-owner', 'manager', 'dispatcher'])) {
            return !in_array($ride->status, ['completed', 'cancelled']);
        }

        return $ride->driver_id === $user->id && $ride->status === 'in_progress';
    }

    public function delete(User $user, TaxiRide $ride): bool
    {
        if ($user->tenant_id !== $ride->tenant_id) {
            return false;
        }

        return $user->hasAnyRole(['admin', 'tenant-owner', 'manager']) &&
               in_array($ride->status, ['pending', 'cancelled']);
    }

    public function restore(User $user, TaxiRide $ride): bool
    {
        if ($user->tenant_id !== $ride->tenant_id) {
            return false;
        }

        return $user->hasAnyRole(['admin', 'tenant-owner', 'manager']);
    }

    public function forceDelete(User $user, TaxiRide $ride): bool
    {
        if ($user->tenant_id !== $ride->tenant_id) {
            return false;
        }

        return $user->hasRole('admin');
    }
}
