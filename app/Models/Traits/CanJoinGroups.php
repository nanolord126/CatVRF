<?php

declare(strict_types=1);

namespace App\Models\Traits;

use App\Models\GroupMember;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\MorphMany;

trait CanJoinGroups
{
    public function groups(): MorphMany
    {
        return $this->morphMany(GroupMember::class, 'groupable');
    }

    public function groupsCount(): int
    {
        return $this->groups()->count();
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
