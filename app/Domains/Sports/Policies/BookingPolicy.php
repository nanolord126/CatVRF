<?php declare(strict_types=1);

namespace App\Domains\Sports\Policies;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class BookingPolicy extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    public function viewAny(User $user): Response
        {
            return $this->response->allow();
        }

        public function view(User $user, Booking $booking): Response
        {
            return ($user->id === $booking->member_id || $user->hasRole('admin')) ? $this->response->allow() : $this->response->deny();
        }

        public function create(User $user): Response
        {
            return $this->response->allow();
        }

        public function update(User $user, Booking $booking): Response
        {
            return ($user->id === $booking->member_id || $user->hasRole('admin')) ? $this->response->allow() : $this->response->deny();
        }

        public function cancel(User $user, Booking $booking): Response
        {
            return ($user->id === $booking->member_id || $user->hasRole('admin')) ? $this->response->allow() : $this->response->deny();
        }
}
