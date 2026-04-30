<?php

declare(strict_types=1);

namespace App\Models\Traits;

use Carbon\CarbonImmutable;

trait CanBeBanned
{
    public function ban(): void
    {
        $this->banned_at = CarbonImmutable::now();
        $this->save();
    }

    public function unban(): void
    {
        $this->banned_at = null;
        $this->save();
    }

    public function isBanned(): bool
    {
        return $this->banned_at !== null;
    }
}
