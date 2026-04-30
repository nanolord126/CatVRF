<?php

declare(strict_types=1);

namespace App\Models\Traits;

use App\Models\Friendship;
use Illuminate\Database\Eloquent\Relations\MorphMany;

trait CanHaveFriends
{
    public function friends(): MorphMany
    {
        return $this->morphMany(Friendship::class, 'friendable');
    }

    public function friendsCount(): int
    {
        return $this->friends()->where('status', 'accepted')->count();
    }
}
