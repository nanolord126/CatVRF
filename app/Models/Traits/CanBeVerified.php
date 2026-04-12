<?php declare(strict_types=1);

namespace App\Models\Traits;

trait CanBeVerified
{
    public function verifications(): \Illuminate\Database\Eloquent\Relations\MorphMany
    {
        return $this->morphMany(Verification::class, 'verifiable');
    }

    public function isVerified(): bool
    {
        return $this->verifications()->where('status', 'approved')->exists();
    }
}
