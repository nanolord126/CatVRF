<?php

declare(strict_types=1);

namespace App\Domains\EventPlanning\Entertainment\Policies;

use App\Domains\EventPlanning\Entertainment\Models\Event;
use App\Models\User;

/**
 * КАНОН 2026 — EVENT POLICY
 */
final class EventPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('view_events');
    }

    public function view(User $user, Event $event): bool
    {
        return $user->tenant_id === $event->tenant_id;
    }

    public function create(User $user): bool
    {
        return $user->can('manage_entertainment');
    }

    public function update(User $user, Event $event): bool
    {
        return $user->tenant_id === $event->tenant_id && $user->can('manage_entertainment');
    }

    public function delete(User $user, Event $event): bool
    {
        return $user->tenant_id === $event->tenant_id && $user->hasRole('admin');
    }
}
