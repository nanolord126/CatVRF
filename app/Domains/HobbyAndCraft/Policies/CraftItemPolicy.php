<?php declare(strict_types=1);

namespace App\Domains\HobbyAndCraft\Policies;

use App\Models\User;
use App\Domains\HobbyAndCraft\Models\CraftItem;
use Illuminate\Auth\Access\HandlesAuthorization;

final class CraftItemPolicy
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
    public function view(User $user, CraftItem $craftItem): bool
    {
        return $user->tenant_id === $craftItem->tenant_id;
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
    public function update(User $user, CraftItem $craftItem): bool
    {
        return $user->tenant_id === $craftItem->tenant_id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, CraftItem $craftItem): bool
    {
        return $user->tenant_id === $craftItem->tenant_id;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, CraftItem $craftItem): bool
    {
        return $user->tenant_id === $craftItem->tenant_id;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, CraftItem $craftItem): bool
    {
        return false;
    }
}
