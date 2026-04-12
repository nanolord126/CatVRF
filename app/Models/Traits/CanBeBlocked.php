<?php declare(strict_types=1);

namespace App\Models\Traits;

trait CanBeBlocked
{
    public function block(): void
    {
        $this->blocked_at = now();
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
