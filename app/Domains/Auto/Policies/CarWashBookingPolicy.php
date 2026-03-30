<?php declare(strict_types=1);

namespace App\Domains\Auto\Policies;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class CarWashBookingPolicy extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    public function viewAny(User $user): bool
        {
            return true;
        }

        public function view(User $user, CarWashBooking $booking): bool
        {
            return $user->id === $booking->client_id || $user->isAdmin();
        }

        public function create(User $user): bool
        {
            return $user->isVerified();
        }

        public function cancel(User $user, CarWashBooking $booking): Response
        {
            if ($user->id !== $booking->client_id && !$user->isAdmin()) {
                return $this->response->deny('Вы не можете отменить эту бронь');
            }

            if ($booking->status === 'completed' || $booking->status === 'cancelled') {
                return $this->response->deny('Бронь уже завершена или отменена');
            }

            $hoursUntilStart = $booking->scheduled_at->diffInHours(now(), false);
            if ($hoursUntilStart < -24) {
                return $this->response->deny('Отмену можно сделать только за 24 часа до начала');
            }

            return $this->response->allow();
        }
}
