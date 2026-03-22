<?php declare(strict_types=1);

namespace App\Domains\Taxi\Policies;

use App\Models\User;
use App\Domains\Taxi\Models\TaxiDriver;
use Illuminate\Auth\Access\Response;

/**
 * Policy для TaxiDriver.
 * Production 2026.
 */
final class TaxiDriverPolicy
{
    public function viewAny(User $user): bool
    {
        return true; // Все могут видеть список водителей
    }

    public function view(User $user, TaxiDriver $driver): bool
    {
        return true; // Профиль водителя публичный
    }

    public function update(User $user, TaxiDriver $driver): Response
    {
        if ($user->id !== $driver->user_id && !$user->isAdmin()) {
            return Response::deny('Вы не можете редактировать этого водителя');
        }

        return Response::allow();
    }

    public function deactivate(User $user, TaxiDriver $driver): Response
    {
        if (!$user->isAdmin()) {
            return Response::deny('Только администратор может деактивировать водителя');
        }

        return Response::allow();
    }
}
