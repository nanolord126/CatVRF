<?php declare(strict_types=1);

namespace App\Models\Traits;

trait CanHaveFriends
{
    public function friends(): \Illuminate\Database\Eloquent\Relations\MorphMany
    {
        return $this->morphMany(Friendship::class, 'friendable');
    }

    public function friendsCount(): int
    {
        return $this->friends()->where('status', 'accepted')->count();
    }
}
