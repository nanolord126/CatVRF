<?php declare(strict_types=1);

namespace App\Models\Traits;

trait CanBeFollowed
{
    public function followers(): \Illuminate\Database\Eloquent\Relations\MorphMany
    {
        return $this->morphMany(Follow::class, 'followable');
    }

    public function followersCount(): int
    {
        return $this->followers()->count();
    }
}
