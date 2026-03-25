<?php declare(strict_types=1);

namespace App\Domains\Taxi\Policies;

use App\Models\User;
use App\Domains\Taxi\Models\TaxiRide;
use Illuminate\Auth\Access\Response;

/**
 * Policy для TaxiRide.
 * Production 2026.
 */
final class TaxiRidePolicy
{
    public function viewAny(User $user): bool
    {
        return true; // Все могут видеть список поездок (публичная информация)
    }

    public function view(User $user, TaxiRide $ride): bool
    {
        return $user->id === $ride->passenger_id || $user->id === $ride->driver?->user_id || $user->isAdmin();
    }

    public function create(User $user): bool
    {
        return $user->isVerified();
    }

    public function cancel(User $user, TaxiRide $ride): Response
    {
        if ($user->id !== $ride->passenger_id && !$user->isAdmin()) {
            return $this->response->deny('Вы не можете отменить эту поездку');
        }

        if ($ride->status === 'completed' || $ride->status === 'cancelled') {
            return $this->response->deny('Поездка уже завершена или отменена');
        }

        // Отмену можно сделать только в течение 24 часов до начала
        $hoursUntilStart = $ride->started_at->diffInHours(now(), false);
        if ($hoursUntilStart < -24) {
            return $this->response->deny('Отмену можно сделать только за 24 часа до начала');
        }

        return $this->response->allow();
    }

    public function rate(User $user, TaxiRide $ride): Response
    {
        if ($user->id !== $ride->passenger_id && !$user->isAdmin()) {
            return $this->response->deny('Вы не можете оценить эту поездку');
        }

        if ($ride->status !== 'completed') {
            return $this->response->deny('Можно оценить только завершённую поездку');
        }

        return $this->response->allow();
    }
}
