<?php declare(strict_types=1);

namespace App\Domains\EventPlanning\Entertainment\Policies;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class TicketSalePolicy extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
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
