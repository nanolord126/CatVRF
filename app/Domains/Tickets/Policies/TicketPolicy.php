<?php declare(strict_types=1);

namespace App\Domains\Tickets\Policies;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class TicketPolicy extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
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
