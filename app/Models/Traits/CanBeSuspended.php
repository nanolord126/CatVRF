<?php declare(strict_types=1);

namespace App\Models\Traits;

trait CanBeSuspended
{
    public function suspend(): void
    {
        $this->suspended_at = now();
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
}
