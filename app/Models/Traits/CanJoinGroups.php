<?php declare(strict_types=1);

namespace App\Models\Traits;

trait CanJoinGroups
{
    public function groups(): \Illuminate\Database\Eloquent\Relations\MorphMany
    {
        return $this->morphMany(GroupMember::class, 'groupable');
    }

    public function groupsCount(): int
    {
        return $this->groups()->count();
    }
}
