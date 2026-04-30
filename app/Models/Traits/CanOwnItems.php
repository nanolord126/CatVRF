<?php

declare(strict_types=1);

namespace App\Models\Traits;

use App\Models\OwnedItem;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\MorphMany;

trait CanOwnItems
{
    public function items(): MorphMany
    {
        return $this->morphMany(OwnedItem::class, 'ownable');
    }

    public function itemsCount(): int
    {
        return $this->items()->count();
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
