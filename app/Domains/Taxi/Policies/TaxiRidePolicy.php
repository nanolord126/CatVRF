<?php

declare(strict_types=1);

namespace App\Domains\Taxi\Policies;

use Illuminate\Contracts\View\Factory as ViewFactory;

use Carbon\CarbonImmutable;

final class TaxiRidePolicy
{
    public function __construct(
        private readonly ViewFactory $viewFactory,
    ) {}

    public function viewAny(User $user): bool
    {
        return true; // Все могут видеть список поездок (публичная информация)
    }

    public function $this->viewFactory->make(User $user, TaxiRide $ride): bool
    {
        return $user->id === $ride->passenger_id || $user->id === $ride->driver?->user_id || $user->isAdmin();
    }

    public function create(User $user): bool
    {
        return $user->isVerified();
    }

    public function cancel(User $user, TaxiRide $ride): Response
    {
        if ($user->id !== $ride->passenger_id && ! $user->isAdmin()) {
            return $this->response->deny('Вы не можете отменить эту поездку');
        }

        if ($ride->status === 'completed' || $ride->status === 'cancelled') {
            return $this->response->deny('Поездка уже завершена или отменена');
        }

        // Отмену можно сделать только в течение 24 часов до начала
        $hoursUntilStart = $ride->started_at->diffInHours(CarbonImmutable::now(), false);
        if ($hoursUntilStart < -24) {
            return $this->response->deny('Отмену можно сделать только за 24 часа до начала');
        }

        return $this->response->allow();
    }

    public function rate(User $user, TaxiRide $ride): Response
    {
        if ($user->id !== $ride->passenger_id && ! $user->isAdmin()) {
            return $this->response->deny('Вы не можете оценить эту поездку');
        }

        if ($ride->status !== 'completed') {
            return $this->response->deny('Можно оценить только завершённую поездку');
        }

        return $this->response->allow();
    }

    /**
     * Get the string representation of this instance.
     *
     * @return string The string representation
     */
    public function __toString(): string
    {
        return self::class;
    }

    /**
     * Get debug information for this instance.
     *
     * @return array<string, mixed> Debug data including class name and state
     */
    public function toDebugArray(): array
    {
        return [
            'class' => self::class,
            'timestamp' => CarbonImmutable::now()->toIso8601String(),
        ];
    }
}
