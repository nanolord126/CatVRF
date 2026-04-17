<?php

declare(strict_types=1);

namespace App\Domains\Sports\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

final class FraudDetectionPolicy
{
    use HandlesAuthorization;

    public function detectFraud(User $user, int $targetId): bool
    {
        return $user->hasRole('admin') || $user->hasRole('moderator');
    }

    public function applyPenalty(User $user, int $targetUserId): bool
    {
        return $user->hasRole('admin') || $user->hasRole('moderator');
    }

    public function viewFraudScore(User $user, int $targetUserId): bool
    {
        return $user->id === $targetUserId 
            || $user->hasRole('admin') 
            || $user->hasRole('moderator');
    }
}
