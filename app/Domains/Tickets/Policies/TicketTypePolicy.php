<?php declare(strict_types=1);

namespace App\Domains\Tickets\Policies;

use App\Domains\Tickets\Models\TicketType;
use App\Models\User;
use Illuminate\Auth\Access\Response;

final class TicketTypePolicy
{
    public function viewAny(?User $user): Response
    {
        return Response::allow();
    }

    public function view(?User $user, TicketType $ticketType): Response
    {
        return Response::allow();
    }

    public function create(User $user): Response
    {
        return $user->hasPermission('ticket_types.create')
            ? Response::allow()
            : Response::deny('Unauthorized');
    }

    public function update(User $user, TicketType $ticketType): Response
    {
        if ($user->id === $ticketType->event->organizer_id || $user->isAdmin()) {
            return Response::allow();
        }

        return Response::deny('Unauthorized');
    }

    public function delete(User $user, TicketType $ticketType): Response
    {
        return $user->isAdmin()
            ? Response::allow()
            : Response::deny('Only admins can delete ticket types');
    }
}
