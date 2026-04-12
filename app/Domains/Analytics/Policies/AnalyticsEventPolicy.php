<?php declare(strict_types=1);

namespace App\Domains\Analytics\Policies;

use App\Models\User;
use App\Domains\Analytics\Models\AnalyticsEvent;
final class AnalyticsEventPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->tenant_id !== null;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, AnalyticsEvent $analyticsEvent): bool
    {
        return $user->tenant_id === $analyticsEvent->tenant_id;
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
    public function update(User $user, AnalyticsEvent $analyticsEvent): bool
    {
        return $user->tenant_id === $analyticsEvent->tenant_id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, AnalyticsEvent $analyticsEvent): bool
    {
        return $user->tenant_id === $analyticsEvent->tenant_id;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, AnalyticsEvent $analyticsEvent): bool
    {
        return $user->tenant_id === $analyticsEvent->tenant_id;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, AnalyticsEvent $analyticsEvent): bool
    {
        return false;
    }
}
