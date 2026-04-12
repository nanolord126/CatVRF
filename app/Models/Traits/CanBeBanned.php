<?php declare(strict_types=1);

namespace App\Models\Traits;

trait CanBeBanned
{
    public function ban(): void
    {
        $this->banned_at = now();
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
