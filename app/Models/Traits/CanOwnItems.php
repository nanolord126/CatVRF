<?php declare(strict_types=1);

namespace App\Models\Traits;

trait CanOwnItems
{
    public function items(): \Illuminate\Database\Eloquent\Relations\MorphMany
    {
        return $this->morphMany(OwnedItem::class, 'ownable');
    }

    public function itemsCount(): int
    {
        return $this->items()->count();
    }
}
