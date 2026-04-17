<?php

declare(strict_types=1);

namespace App\Domains\Sports\Policies;

use App\Domains\Sports\Models\LiveStream;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

final class LiveStreamPolicy
{
    use HandlesAuthorization;

    public function view(User $user, LiveStream $stream): bool
    {
        return $stream->status === 'live' 
            || $stream->status === 'ended'
            || $user->id === $stream->user_id
            || $user->hasRole('admin');
    }

    public function create(User $user): bool
    {
        return $user->hasRole('trainer') || $user->hasRole('admin');
    }

    public function update(User $user, LiveStream $stream): bool
    {
        return $user->id === $stream->user_id || $user->hasRole('admin');
    }

    public function delete(User $user, LiveStream $stream): bool
    {
        return $user->id === $stream->user_id || $user->hasRole('admin');
    }

    public function start(User $user, int $streamId): bool
    {
        return $user->hasRole('trainer') || $user->hasRole('admin');
    }

    public function end(User $user, int $streamId): bool
    {
        return $user->hasRole('trainer') || $user->hasRole('admin');
    }

    public function viewRecording(User $user, int $streamId): bool
    {
        return $user->hasRole('trainer') || $user->hasRole('admin') || $user->hasRole('premium');
    }
}
