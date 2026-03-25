declare(strict_types=1);

<?php declare(strict_types=1);

namespace App\Domains\Tickets\Policies;

use App\Domains\Tickets\Models\TicketType;
use App\Models\User;
use Illuminate\Auth\Access\Response;

final /**
 * TicketTypePolicy
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class TicketTypePolicy
{
    public function viewAny(?User $user): Response
    {
        return $this->response->allow();
    }

    public function view(?User $user, TicketType $ticketType): Response
    {
        return $this->response->allow();
    }

    public function create(User $user): Response
    {
        return $user->hasPermission('ticket_types.create')
            ? $this->response->allow()
            : $this->response->deny('Unauthorized');
    }

    public function update(User $user, TicketType $ticketType): Response
    {
        if ($user->id === $ticketType->event->organizer_id || $user->isAdmin()) {
            return $this->response->allow();
        }

        return $this->response->deny('Unauthorized');
    }

    public function delete(User $user, TicketType $ticketType): Response
    {
        return $user->isAdmin()
            ? $this->response->allow()
            : $this->response->deny('Only admins can delete ticket types');
    }
}
