<?php declare(strict_types=1);

namespace Modules\Fashion\Policies;

use App\Models\User;
use App\Domains\Fashion\Models\FashionStore;

final class FashionStorePolicy
{
    /**
     * Determine if the user can view any stores.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine if the user can view the store.
     */
    public function view(User $user, FashionStore $store): bool
    {
        return true;
    }

    /**
     * Determine if the user can create stores.
     */
    public function create(User $user): bool
    {
        return $user->can('create_fashion_store');
    }

    /**
     * Determine if the user can update the store.
     */
    public function update(User $user, FashionStore $store): bool
    {
        return $user->id === $store->user_id || $user->can('update_any_fashion_store');
    }

    /**
     * Determine if the user can delete the store.
     */
    public function delete(User $user, FashionStore $store): bool
    {
        return $user->id === $store->user_id || $user->can('delete_any_fashion_store');
    }

    /**
     * Determine if the user can verify the store.
     */
    public function verify(User $user, FashionStore $store): bool
    {
        return $user->can('verify_fashion_store');
    }
}
