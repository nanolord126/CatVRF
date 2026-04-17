<?php

declare(strict_types=1);

namespace App\Domains\Sports\Policies;

use App\Domains\Sports\Services\AI\SportsPersonalTrainerAIService;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

final class AdaptiveWorkoutPolicy
{
    use HandlesAuthorization;

    public function view(User $user, SportsPersonalTrainerAIService $service, int $targetUserId): bool
    {
        return $user->id === $targetUserId || $user->hasRole('admin');
    }

    public function adjust(User $user, SportsPersonalTrainerAIService $service, int $targetUserId): bool
    {
        return $user->id === $targetUserId;
    }

    public function track(User $user, SportsPersonalTrainerAIService $service, int $targetUserId): bool
    {
        return $user->id === $targetUserId;
    }

    public function delete(User $user, SportsPersonalTrainerAIService $service, int $targetUserId): bool
    {
        return $user->id === $targetUserId || $user->hasRole('admin');
    }
}
