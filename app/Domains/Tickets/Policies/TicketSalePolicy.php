<?php declare(strict_types=1);

namespace App\Domains\Tickets\Policies;

use App\Domains\Tickets\Models\TicketSale;
use App\Models\User;
use Illuminate\Auth\Access\Response;

final class TicketSalePolicy
{
    public function viewAny(User $user): Response
    {
        return Response::allow();
    }

    public function view(User $user, TicketSale $sale): Response
    {
        if ($user->id === $sale->buyer_id || $user->id === $sale->organizer_id || $user->isAdmin()) {
            return Response::allow();
        }

        return Response::deny('Unauthorized');
    }

    public function refund(User $user, TicketSale $sale): Response
    {
        if ($user->id === $sale->organizer_id || $user->isAdmin()) {
            return Response::allow();
        }

        return Response::deny('Unauthorized');
    }
}
