<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Event;
use Illuminate\Auth\Access\HandlesAuthorization;

class EventPolicy extends BaseSecurityPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->role_code === 'manager' || $user->role_code === 'admin';
    }

    public function view(User $user, Event $event): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->role_code === 'manager' || $user->role_code === 'admin';
    }

    public function update(User $user, Event $event): bool
    {
        return $user->role_code === 'manager' || $user->role_code === 'admin';
    }

    public function delete(User $user, Event $event): bool
    {
        return $user->role_code === 'admin';
    }
}

