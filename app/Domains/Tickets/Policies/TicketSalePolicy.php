<?php

declare(strict_types=1);


namespace App\Domains\Tickets\Policies;

use App\Domains\Tickets\Models\TicketSale;
use App\Models\User;
use Illuminate\Auth\Access\Response;

final /**
 * TicketSalePolicy
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class TicketSalePolicy
{
    public function viewAny(User $user): Response
    {
        return $this->response->allow();
    }

    public function view(User $user, TicketSale $sale): Response
    {
        if ($user->id === $sale->buyer_id || $user->id === $sale->organizer_id || $user->isAdmin()) {
            return $this->response->allow();
        }

        return $this->response->deny('Unauthorized');
    }

    public function refund(User $user, TicketSale $sale): Response
    {
        if ($user->id === $sale->organizer_id || $user->isAdmin()) {
            return $this->response->allow();
        }

        return $this->response->deny('Unauthorized');
    }
}
