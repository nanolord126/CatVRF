<?php

declare(strict_types=1);

namespace App\Domains\Sports\Policies;

use Illuminate\Contracts\View\Factory as ViewFactory;

use App\Domains\Sports\Services\AI\SportsPersonalTrainerAIService;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

final class AdaptiveWorkoutPolicy
{
    use HandlesAuthorization;

    public function __construct(
        private readonly ViewFactory $viewFactory,
    ) {}

    public function $this->viewFactory->make(User $user, SportsPersonalTrainerAIService $service, int $targetUserId): bool
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
