<?php declare(strict_types=1);

namespace App\Policies\Domains;

use App\Domains\Taxi\Models\TaxiRide;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

final class TaxiRidePolicy
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
     * View ride (driver, passenger, dispatcher, manager, admin)
     */
    public function view(User $user, TaxiRide $ride): bool
    {
        // Tenant scoping
        if ($ride->tenant_id !== $user->tenant_id) {
            return false;
        }

        // Driver can view their own rides
        if ($user->id === $ride->driver_id) {
            return true;
        }

        // Passenger can view their own rides
        if ($user->id === $ride->passenger_id) {
            return true;
        }

        // Manager/accountant can view all rides
        return $user->hasRole(['manager', 'accountant']);
    }

    /**
     * Create ride (passenger)
     */
    public function create(User $user): bool
    {
        return $user->hasRole(['passenger', 'business_owner', 'manager']);
    }

    /**
     * Accept ride (driver)
     */
    public function accept(User $user, TaxiRide $ride): bool
    {
        if ($ride->tenant_id !== $user->tenant_id) {
            return false;
        }

        return $user->hasRole('driver') && $user->id === $ride->driver_id;
    }

    /**
     * Complete ride (driver)
     */
    public function complete(User $user, TaxiRide $ride): bool
    {
        if ($ride->tenant_id !== $user->tenant_id) {
            return false;
        }

        return $user->hasRole('driver') && $user->id === $ride->driver_id;
    }

    /**
     * Rate ride (passenger after completion)
     */
    public function rate(User $user, TaxiRide $ride): bool
    {
        if ($ride->tenant_id !== $user->tenant_id) {
            return false;
        }

        return $user->id === $ride->passenger_id && $ride->status === 'completed';
    }

    /**
     * Cancel ride
     */
    public function cancel(User $user, TaxiRide $ride): bool
    {
        if ($ride->tenant_id !== $user->tenant_id) {
            return false;
        }

        // Passenger can cancel before driver acceptance
        if ($user->id === $ride->passenger_id && $ride->status === 'pending') {
            return true;
        }

        // Driver can cancel with reason if emergency
        if ($user->id === $ride->driver_id && $ride->status === 'accepted') {
            return true;
        }

        // Admin/manager can cancel
        return $user->hasRole(['admin', 'manager']);
    }

    /**
     * Update ride (manager/admin only)
     */
    public function update(User $user, TaxiRide $ride): bool
    {
        if ($ride->tenant_id !== $user->tenant_id) {
            return false;
        }

        return $user->hasRole(['admin', 'manager']);
    }

    /**
     * Delete ride (admin only)
     */
    public function delete(User $user, TaxiRide $ride): bool
    {
        if ($ride->tenant_id !== $user->tenant_id) {
            return false;
        }

        return $user->hasRole('admin');
    }
}
