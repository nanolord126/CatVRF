<?php declare(strict_types=1);

namespace App\Domains\HouseholdGoods\Policies;

use App\Models\User;
use App\Domains\HouseholdGoods\Models\HouseholdProduct;
final class HouseholdProductPolicy
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
    public function view(User $user, HouseholdProduct $householdProduct): bool
    {
        return $user->tenant_id === $householdProduct->tenant_id;
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
    public function update(User $user, HouseholdProduct $householdProduct): bool
    {
        return $user->tenant_id === $householdProduct->tenant_id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, HouseholdProduct $householdProduct): bool
    {
        return $user->tenant_id === $householdProduct->tenant_id;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, HouseholdProduct $householdProduct): bool
    {
        return $user->tenant_id === $householdProduct->tenant_id;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, HouseholdProduct $householdProduct): bool
    {
        return false;
    }
}
