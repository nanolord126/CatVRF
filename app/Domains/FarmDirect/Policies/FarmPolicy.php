<?php declare(strict_types=1);

namespace App\Domains\FarmDirect\Policies;

use App\Models\User;
use App\Domains\FarmDirect\Models\Farm;
final class FarmPolicy
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
    public function view(User $user, Farm $farm): bool
    {
        return $user->tenant_id === $farm->tenant_id;
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
    public function update(User $user, Farm $farm): bool
    {
        return $user->tenant_id === $farm->tenant_id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Farm $farm): bool
    {
        return $user->tenant_id === $farm->tenant_id;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Farm $farm): bool
    {
        return $user->tenant_id === $farm->tenant_id;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Farm $farm): bool
    {
        return false;
    }
}
