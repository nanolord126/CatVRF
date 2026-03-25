declare(strict_types=1);

<?php declare(strict_types=1);

namespace App\Domains\Entertainment\Policies;

use App\Models\User;
use App\Domains\Entertainment\Models\TicketSale;
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
        return auth()->check() ? $this->response->allow() : $this->response->deny('Unauthorized');
    }

    public function view(User $user, TicketSale $ticket): Response
    {
        return $user->id === $ticket->booking->customer_id || $user->hasPermissionTo('view_tickets')
            ? $this->response->allow()
            : $this->response->deny('Unauthorized');
    }

    public function create(User $user): Response
    {
        return $user->hasPermissionTo('create_tickets')
            ? $this->response->allow()
            : $this->response->deny('Unauthorized');
    }

    public function refund(User $user, TicketSale $ticket): Response
    {
        return $user->id === $ticket->booking->customer_id || $user->hasPermissionTo('refund_tickets')
            ? $this->response->allow()
            : $this->response->deny('Unauthorized');
    }
}
