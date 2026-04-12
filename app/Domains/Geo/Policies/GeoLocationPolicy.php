<?php declare(strict_types=1);

namespace App\Domains\Geo\Policies;

use App\Models\User;
use App\Domains\Geo\Models\GeoLocation;
final class GeoLocationPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->tenant_id !== null;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, GeoLocation $geoLocation): bool
    {
        return $user->tenant_id === $geoLocation->tenant_id;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->tenant_id !== null;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, GeoLocation $geoLocation): bool
    {
        return $user->tenant_id === $geoLocation->tenant_id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, GeoLocation $geoLocation): bool
    {
        return $user->tenant_id === $geoLocation->tenant_id;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, GeoLocation $geoLocation): bool
    {
        return $user->tenant_id === $geoLocation->tenant_id;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, GeoLocation $geoLocation): bool
    {
        return false;
    }
}
