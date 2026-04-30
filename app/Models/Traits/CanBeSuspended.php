<?php

declare(strict_types=1);

namespace App\Models\Traits;

use Carbon\CarbonImmutable;

trait CanBeSuspended
{
    public function suspend(): void
    {
        $this->suspended_at = CarbonImmutable::now();
        $this->save();
    }

    public function unsuspend(): void
    {
        $this->suspended_at = null;
        $this->save();
    }

    public function isSuspended(): bool
    {
        return $this->suspended_at !== null;
    }

    public function getSuspendedAt(): ?CarbonImmutable
    {
        return $this->suspended_at
            ? CarbonImmutable::parse($this->suspended_at)
            : null;
    }
}
