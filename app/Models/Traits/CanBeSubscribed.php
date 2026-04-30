<?php

declare(strict_types=1);

namespace App\Models\Traits;

use App\Models\Subscription;
use Illuminate\Database\Eloquent\Relations\MorphMany;

trait CanBeSubscribed
{
    public function subscribers(): MorphMany
    {
        return $this->morphMany(Subscription::class, 'subscribable');
    }

    public function subscribersCount(): int
    {
        return $this->subscribers()->count();
    }
}
