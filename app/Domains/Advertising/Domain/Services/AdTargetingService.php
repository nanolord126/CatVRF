<?php

declare(strict_types=1);

namespace App\Domains\Advertising\Domain\Services;

use App\Domains\Advertising\Domain\Entities\AdCampaign;
use App\Models\User;
use Illuminate\Support\Collection;

final readonly class AdTargetingService
{
    public function filterCampaignsForUser(Collection $campaigns, User $user): Collection
    {
        return $campaigns->filter(function (AdCampaign $campaign) use ($user) {
            if (!$campaign->isActive() || !$campaign->hasBudget()) {
                return false;
            }

            $criteria = $campaign->targeting_criteria;

            // Vertical targeting
            if (isset($criteria['verticals']) && !in_array($user->active_vertical, $criteria['verticals'])) {
                return false;
            }

            // Geo targeting
            if (isset($criteria['geo']) && !$this->isUserInGeo($user, $criteria['geo'])) {
                return false;
            }
            
            // User taste profile targeting
            if (isset($criteria['taste_profile']) && !$this->matchTasteProfile($user, $criteria['taste_profile'])) {
                return false;
            }

            return true;
        });
    }

    private function isUserInGeo(User $user, array $geoCriteria): bool
    {
        // Placeholder for actual geo-fencing logic
        return true;
    }

    private function matchTasteProfile(User $user, array $tasteCriteria): bool
    {
        $userProfile = $user->taste_profile ?? [];
        if (empty($userProfile)) {
            return false; // Or true if we want to show ads to users without a profile
        }

        foreach ($tasteCriteria as $key => $value) {
            if (!isset($userProfile[$key]) || $userProfile[$key] !== $value) {
                return false;
            }
        }

        return true;
    }
}
