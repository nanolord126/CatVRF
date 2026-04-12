<?php declare(strict_types=1);

namespace App\Models\Traits;

use Illuminate\Database\Eloquent\Model;

trait CanBeLiked
{
    public function likes()
    {
        return $this->morphMany(Like::class, 'likeable');
    }

    public function like(Model $user)
    {
        $this->likes()->firstOrCreate(['user_id' => $user->getKey()]);
    }
}
