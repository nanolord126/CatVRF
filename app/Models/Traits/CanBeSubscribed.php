<?php declare(strict_types=1);

namespace App\Models\Traits;

trait CanBeSubscribed
{
    public function subscribers(): \Illuminate\Database\Eloquent\Relations\MorphMany
    {
        return $this->morphMany(Subscription::class, 'subscribable');
    }

    public function subscribersCount(): int
    {
        return $this->subscribers()->count();
    }
}
