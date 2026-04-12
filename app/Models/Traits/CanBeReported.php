<?php declare(strict_types=1);

namespace App\Models\Traits;

trait CanBeReported
{
    public function reports(): \Illuminate\Database\Eloquent\Relations\MorphMany
    {
        return $this->morphMany(Report::class, 'reportable');
    }

    public function reportsCount(): int
    {
        return $this->reports()->count();
    }
}
