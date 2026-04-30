<?php

declare(strict_types=1);

namespace App\Models\Traits;

use App\Models\TeamMember;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\MorphMany;

trait CanManageTeams
{
    public function teams(): MorphMany
    {
        return $this->morphMany(TeamMember::class, 'teamable');
    }

    public function teamsCount(): int
    {
        return $this->teams()->count();
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
