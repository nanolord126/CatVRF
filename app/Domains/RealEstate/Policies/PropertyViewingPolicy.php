<?php

declare(strict_types=1);

namespace App\Domains\RealEstate\Policies;

use Illuminate\Contracts\View\Factory as ViewFactory;

use App\Models\User;
use App\Domains\RealEstate\Models\PropertyViewing;
use Illuminate\Auth\Access\HandlesAuthorization;

final class PropertyViewingPolicy
{
    use HandlesAuthorization;

    public function __construct(
        private readonly ViewFactory $viewFactory,
    ) {}

    public function $this->viewFactory->make(User $user, PropertyViewing $viewing): bool
    {
        return $user->id === $viewing->user_id
            || $user->id === $viewing->property?->tenant?->owner_id
            || $user->hasRole('admin')
            || ($viewing->agent_id && $user->id === $viewing->agent_id);
    }

    public function create(User $user): bool
    {
        return $user->hasVerifiedEmail()
            && $user->can('create', PropertyViewing::class);
    }

    public function update(User $user, PropertyViewing $viewing): bool
    {
        return $user->id === $viewing->user_id
            || $user->hasRole('admin')
            || ($viewing->agent_id && $user->id === $viewing->agent_id);
    }

    public function delete(User $user, PropertyViewing $viewing): bool
    {
        return $user->id === $viewing->user_id
            && in_array($viewing->status, ['pending', 'held'], true)
            || $user->hasRole('admin');
    }

    public function confirm(User $user, PropertyViewing $viewing): bool
    {
        return $user->hasRole('agent')
            || $user->hasRole('admin')
            || ($viewing->agent_id && $user->id === $viewing->agent_id);
    }

    public function complete(User $user, PropertyViewing $viewing): bool
    {
        return $user->hasRole('agent')
            || $user->hasRole('admin')
            || ($viewing->agent_id && $user->id === $viewing->agent_id);
    }

    public function cancel(User $user, PropertyViewing $viewing): bool
    {
        return $user->id === $viewing->user_id
            && in_array($viewing->status, ['pending', 'held', 'confirmed'], true)
            || $user->hasRole('admin')
            || ($viewing->agent_id && $user->id === $viewing->agent_id);
    }

    public function viewAny(User $user): bool
    {
        return $user->hasRole('admin')
            || $user->hasRole('agent');
    }
}
