<?php

declare(strict_types=1);

namespace App\Domains\HealthAndSports\SubVerticals\SportsNutrition\Models;

use Illuminate\Database\Eloquent\Builder;

trait SportsNutritionDomainTrait
{
    public function scopeForTenant(Builder $query, int $tenantId): Builder
    {
        return $query->where('tenant_id', $tenantId);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->whereNull('deleted_at');
    }
}
