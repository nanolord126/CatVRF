declare(strict_types=1);

<?php declare(strict_types=1);

namespace App\Domains\Tickets\Policies;

use App\Domains\Tickets\Models\Ticket;
use App\Models\User;
use Illuminate\Auth\Access\Response;

final /**
 * TicketPolicy
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class TicketPolicy
{
    public function viewAny(User $user): Response
    {
        return $this->response->allow();
    }

    public function view(User $user, Ticket $ticket): Response
    {
        if ($user->id === $ticket->buyer_id || $user->isAdmin()) {
            return $this->response->allow();
        }

        return $this->response->deny('Unauthorized');
    }

    public function download(User $user, Ticket $ticket): Response
    {
        if ($user->id === $ticket->buyer_id || $user->isAdmin()) {
            return $this->response->allow();
        }

        return $this->response->deny('Unauthorized');
    }
}
