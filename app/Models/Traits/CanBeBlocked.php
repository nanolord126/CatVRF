<?php

declare(strict_types=1);

namespace App\Models\Traits;

use Carbon\CarbonImmutable;

trait CanBeBlocked
{
    public function block(): void
    {
        $this->blocked_at = CarbonImmutable::now();
        $this->save();
    }

    public function unblock(): void
    {
        $this->blocked_at = null;
        $this->save();
    }

    public function isBlocked(): bool
    {
        return $this->blocked_at !== null;
    }
}
