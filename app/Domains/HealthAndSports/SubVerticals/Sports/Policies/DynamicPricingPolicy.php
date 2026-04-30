<?php

declare(strict_types=1);

namespace App\Domains\Sports\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

final class DynamicPricingPolicy
{
    use HandlesAuthorization;

    public function calculatePrice(User $user, int $venueId): bool
    {
        return $user->hasRole('user') || $user->hasRole('admin');
    }

    public function createFlashMembership(User $user): bool
    {
        return $user->hasRole('user') || $user->hasRole('admin');
    }

    public function getBulkPricing(User $user): bool
    {
        return $user->hasRole('business') || $user->hasRole('admin');
    }

    public function updatePricing(User $user, int $venueId): bool
    {
        return $user->hasRole('admin') || $user->hasRole('venue_manager');
    }
}
