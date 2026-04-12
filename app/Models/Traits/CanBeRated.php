<?php declare(strict_types=1);

namespace App\Models\Traits;

use Illuminate\Database\Eloquent\Model;

trait CanBeRated
{
    public function ratings()
    {
        return $this->morphMany(Rating::class, 'rateable');
    }

    public function rate(Model $user, int $rating, ?string $comment = null): void
    {
        $this->ratings()->updateOrCreate(
            ['user_id' => $user->getKey()],
            ['rating' => $rating, 'comment' => $comment]
        );
    }

    public function averageRating(): float
    {
        return (float) $this->ratings()->avg('rating');
    }
}
