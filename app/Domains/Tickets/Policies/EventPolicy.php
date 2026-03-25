declare(strict_types=1);

<?php declare(strict_types=1);

namespace App\Domains\Tickets\Policies;

use App\Domains\Tickets\Models\Event;
use App\Models\User;
use Illuminate\Auth\Access\Response;

final /**
 * EventPolicy
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class EventPolicy
{
    public function viewAny(?User $user): Response
    {
        return $this->response->allow();
    }

    public function view(?User $user, Event $event): Response
    {
        return $this->response->allow();
    }

    public function create(User $user): Response
    {
        return $user->hasPermission('events.create')
            ? $this->response->allow()
            : $this->response->deny('Unauthorized');
    }

    public function update(User $user, Event $event): Response
    {
        if ($user->id === $event->organizer_id || $user->isAdmin()) {
            return $this->response->allow();
        }

        return $this->response->deny('Unauthorized');
    }

    public function delete(User $user, Event $event): Response
    {
        return $user->isAdmin()
            ? $this->response->allow()
            : $this->response->deny('Only admins can delete events');
    }
}
