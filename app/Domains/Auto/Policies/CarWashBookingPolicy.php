<?php

declare(strict_types=1);


namespace App\Domains\Auto\Policies;

use App\Models\User;
use App\Domains\Auto\Models\CarWashBooking;
use Illuminate\Auth\Access\Response;

/**
 * Policy для CarWashBooking.
 * Production 2026.
 */
final class CarWashBookingPolicy
{
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
