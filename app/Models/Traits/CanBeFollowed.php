<?php

declare(strict_types=1);

namespace App\Models\Traits;

use App\Models\Follow;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\MorphMany;

trait CanBeFollowed
{
    public function followers(): MorphMany
    {
        return $this->morphMany(Follow::class, 'followable');
    }

    public function followersCount(): int
    {
        return $this->followers()->count();
    }

    public function scopeForTenant(Builder $query, int $tenantId): Builder
    {
        return $query->where('tenant_id', $tenantId);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}
