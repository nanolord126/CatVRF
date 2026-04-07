<?php declare(strict_types=1);

namespace App\Domains\Pharmacy\Policies;

use App\Models\User;
use App\Domains\Pharmacy\Models\Pharmacy;
use Illuminate\Auth\Access\HandlesAuthorization;

final class PharmacyPolicy
{
    use HandlesAuthorization;

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
    public function view(User $user, Pharmacy $pharmacy): bool
    {
        return $user->tenant_id === $pharmacy->tenant_id;
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
    public function update(User $user, Pharmacy $pharmacy): bool
    {
        return $user->tenant_id === $pharmacy->tenant_id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Pharmacy $pharmacy): bool
    {
        return $user->tenant_id === $pharmacy->tenant_id;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Pharmacy $pharmacy): bool
    {
        return $user->tenant_id === $pharmacy->tenant_id;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Pharmacy $pharmacy): bool
    {
        return false;
    }
}
