<?php declare(strict_types=1);

namespace App\Domains\Tickets\Policies;

use App\Domains\Tickets\Models\Ticket;
use App\Models\User;
use Illuminate\Auth\Access\Response;

final class TicketPolicy
{
    public function viewAny(User $user): Response
    {
        return Response::allow();
    }

    public function view(User $user, Ticket $ticket): Response
    {
        if ($user->id === $ticket->buyer_id || $user->isAdmin()) {
            return Response::allow();
        }

        return Response::deny('Unauthorized');
    }

    public function download(User $user, Ticket $ticket): Response
    {
        if ($user->id === $ticket->buyer_id || $user->isAdmin()) {
            return Response::allow();
        }

        return Response::deny('Unauthorized');
    }
}
