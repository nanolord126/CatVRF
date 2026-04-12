<?php declare(strict_types=1);

namespace App\Domains\Marketplace\Policies;

use App\Models\User;
use App\Domains\Marketplace\Models\MarketplaceListing;
final class MarketplaceListingPolicy
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
    public function view(User $user, MarketplaceListing $marketplaceListing): bool
    {
        return $user->tenant_id === $marketplaceListing->tenant_id;
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
    public function update(User $user, MarketplaceListing $marketplaceListing): bool
    {
        return $user->tenant_id === $marketplaceListing->tenant_id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, MarketplaceListing $marketplaceListing): bool
    {
        return $user->tenant_id === $marketplaceListing->tenant_id;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, MarketplaceListing $marketplaceListing): bool
    {
        return $user->tenant_id === $marketplaceListing->tenant_id;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, MarketplaceListing $marketplaceListing): bool
    {
        return false;
    }
}
