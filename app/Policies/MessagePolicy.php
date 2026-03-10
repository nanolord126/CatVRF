<?php

namespace App\Policies;

use App\Domains\Communication\Models\Message;
use App\Models\User;

class MessagePolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Message $message): bool
    {
        return $user->id === $message->user_id || $user->id === $message->recipient_id;
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('send_messages');
    }

    public function update(User $user, Message $message): bool
    {
        return $user->id === $message->user_id && $user->hasPermissionTo('update_messages');
    }

    public function delete(User $user, Message $message): bool
    {
        return $user->id === $message->user_id && $user->hasPermissionTo('delete_messages');
    }
}
