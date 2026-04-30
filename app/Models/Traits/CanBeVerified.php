<?php

declare(strict_types=1);

namespace App\Models\Traits;

use App\Models\Verification;
use Illuminate\Database\Eloquent\Relations\MorphMany;

trait CanBeVerified
{
    public function verifications(): MorphMany
    {
        return $this->morphMany(Verification::class, 'verifiable');
    }

    public function isVerified(): bool
    {
        return $this->verifications()->where('status', 'approved')->exists();
    }
}
