<?php

declare(strict_types=1);

namespace App\Traits;

use Illuminate\Database\Eloquent\Relations\MorphMany;
use App\Models\FeatureFlag;

trait HasEcosystemFeatures
{
    public function featureFlags(): MorphMany
    {
        return $this->morphMany(FeatureFlag::class, 'featurable');
    }

    public function hasFeatureEnabled(string $featureName): bool
    {
        return $this->featureFlags()
            ->where('name', $featureName)
            ->where('is_enabled', true)
            ->exists();
    }

    public function getEnabledFeatures(): array
    {
        return $this->featureFlags()
            ->where('is_enabled', true)
            ->pluck('name')
            ->toArray();
    }
}
