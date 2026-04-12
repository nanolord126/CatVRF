<?php declare(strict_types=1);

namespace App\Models\Traits;

trait CanManageTeams
{
    public function teams(): \Illuminate\Database\Eloquent\Relations\MorphMany
    {
        return $this->morphMany(TeamMember::class, 'teamable');
    }

    public function teamsCount(): int
    {
        return $this->teams()->count();
    }
}
