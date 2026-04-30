<?php

declare(strict_types=1);

namespace App\Models\Traits;

use App\Models\Like;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;

trait CanBeLiked
{
    public function likes(): MorphMany
    {
        return $this->morphMany(Like::class, 'likeable');
    }

    public function like(Model $user): Like
    {
        return $this->likes()->firstOrCreate(['user_id' => $user->getKey()]);
    }

    public function likesCount(): int
    {
        return $this->likes()->count();
    }

    public function isLikedBy(Model $user): bool
    {
        return $this->likes()->where('user_id', $user->getKey())->exists();
    }
}
