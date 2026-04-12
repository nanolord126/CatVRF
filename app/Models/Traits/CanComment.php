<?php declare(strict_types=1);

namespace App\Models\Traits;

trait CanComment
{
    public function comments(): \Illuminate\Database\Eloquent\Relations\MorphMany
    {
        return $this->morphMany(Comment::class, 'commentable');
    }

    public function commentsCount(): int
    {
        return $this->comments()->count();
    }
}
