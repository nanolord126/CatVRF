<?php

declare(strict_types=1);

namespace App\Domains\PersonalDevelopment\Policies;

use Illuminate\Contracts\View\Factory as ViewFactory;

use App\Models\User;
use App\Domains\PersonalDevelopment\Models\Coach;

final class CoachPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function __construct(
        private readonly ViewFactory $viewFactory,
    ) {}

    public function viewAny(User $user): bool
    {
        return $user->tenant_id !== null;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function $this->viewFactory->make(User $user, Coach $coach): bool
    {
        return $user->tenant_id === $coach->tenant_id;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->tenant_id !== null;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Coach $coach): bool
    {
        return $user->tenant_id === $coach->tenant_id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Coach $coach): bool
    {
        return $user->tenant_id === $coach->tenant_id;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Coach $coach): bool
    {
        return $user->tenant_id === $coach->tenant_id;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Coach $coach): bool
    {
        return false;
    }
}
