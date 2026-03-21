<?php declare(strict_types=1);

namespace App\Domains\Entertainment\Policies;

use App\Models\User;
use App\Domains\Entertainment\Models\TicketSale;
use Illuminate\Auth\Access\Response;

final class TicketSalePolicy
{
    public function viewAny(User $user): Response
    {
        return auth()->check() ? Response::allow() : Response::deny('Unauthorized');
    }

    public function view(User $user, TicketSale $ticket): Response
    {
        return $user->id === $ticket->booking->customer_id || $user->hasPermissionTo('view_tickets')
            ? Response::allow()
            : Response::deny('Unauthorized');
    }

    public function create(User $user): Response
    {
        return $user->hasPermissionTo('create_tickets')
            ? Response::allow()
            : Response::deny('Unauthorized');
    }

    public function refund(User $user, TicketSale $ticket): Response
    {
        return $user->id === $ticket->booking->customer_id || $user->hasPermissionTo('refund_tickets')
            ? Response::allow()
            : Response::deny('Unauthorized');
    }
}
