<?php

declare(strict_types=1);


namespace App\Domains\Sports\Policies;

use App\Domains\Sports\Models\Booking;
use App\Models\User;
use Illuminate\Auth\Access\Response;

final /**
 * BookingPolicy
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class BookingPolicy
{
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
