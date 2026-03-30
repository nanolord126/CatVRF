<?php declare(strict_types=1);

namespace App\Domains\EventPlanning\Entertainment\Policies;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class BookingPolicy extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    public function viewAny(User $user): Response
        {
            return auth()->check() ? $this->response->allow() : $this->response->deny('Unauthorized');
        }

        public function view(User $user, Booking $booking): Response
        {
            return $user->id === $booking->customer_id || $user->hasPermissionTo('view_bookings')
                ? $this->response->allow()
                : $this->response->deny('Unauthorized');
        }

        public function create(User $user): Response
        {
            return auth()->check() ? $this->response->allow() : $this->response->deny('Unauthorized');
        }

        public function cancel(User $user, Booking $booking): Response
        {
            return $user->id === $booking->customer_id || $user->hasPermissionTo('cancel_bookings')
                ? $this->response->allow()
                : $this->response->deny('Unauthorized');
        }
}
