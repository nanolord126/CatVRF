<?php

namespace App\Domains\Communication\Policies;

use App\Models\User;
use App\Domains\Communication\Models\Message;

class MessagePolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Message $message): bool
    {
        return ($user->id === $message->sender_id || $user->id === $message->receiver_id) && $user->tenant_id === $message->tenant_id;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Message $message): bool
    {
        return $user->id === $message->sender_id && $user->tenant_id === $message->tenant_id;
    }

    public function delete(User $user, Message $message): bool
    {
        return ($user->id === $message->sender_id || $user->id === $message->receiver_id) && $user->tenant_id === $message->tenant_id;
    }
}
