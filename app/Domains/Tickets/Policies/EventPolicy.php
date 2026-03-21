<?php declare(strict_types=1);

namespace App\Domains\Tickets\Policies;

use App\Domains\Tickets\Models\Event;
use App\Models\User;
use Illuminate\Auth\Access\Response;

final class EventPolicy
{
    public function viewAny(?User $user): Response
    {
        return Response::allow();
    }

    public function view(?User $user, Event $event): Response
    {
        return Response::allow();
    }

    public function create(User $user): Response
    {
        return $user->hasPermission('events.create')
            ? Response::allow()
            : Response::deny('Unauthorized');
    }

    public function update(User $user, Event $event): Response
    {
        if ($user->id === $event->organizer_id || $user->isAdmin()) {
            return Response::allow();
        }

        return Response::deny('Unauthorized');
    }

    public function delete(User $user, Event $event): Response
    {
        return $user->isAdmin()
            ? Response::allow()
            : Response::deny('Only admins can delete events');
    }
}
