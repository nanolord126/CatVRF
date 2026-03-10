<?php

namespace App\Domains\Events\Policies;

use App\Domains\Events\Models\Event;
use App\Models\User;

class EventPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->tenant_id === tenant()->id;
    }

    public function view(User $user, Event $event): bool
    {
        return $user->tenant_id === $event->tenant_id;
    }

    public function create(User $user): bool
    {
        return $user->tenant_id === tenant()->id && $user->hasPermissionTo('create_events');
    }

    public function update(User $user, Event $event): bool
    {
        return $user->tenant_id === $event->tenant_id && 
               ($user->id === $event->organizer_id || $user->hasPermissionTo('update_events'));
    }

    public function delete(User $user, Event $event): bool
    {
        return $user->tenant_id === $event->tenant_id && 
               ($user->id === $event->organizer_id || $user->hasPermissionTo('delete_events'));
    }
}
