<?php

declare(strict_types=1);


namespace App\Domains\Food\Policies;

use App\Models\User;
use App\Domains\Food\Models\Restaurant;
use Illuminate\Auth\Access\Response;

/**
 * Policy для Restaurant.
 * Production 2026.
 */
final class RestaurantPolicy
{
    public function viewAny(User $user): bool
    {
        return true; // Все могут видеть список ресторанов
    }

    public function view(User $user, Restaurant $restaurant): bool
    {
        return true; // Профиль ресторана публичный
    }

    public function create(User $user): Response
    {
        if (!$user->can('create_restaurant')) {
            return $this->response->deny('Вы не можете создавать рестораны');
        }

        return $this->response->allow();
    }

    public function update(User $user, Restaurant $restaurant): Response
    {
        if ($user->id !== $restaurant->owner_id && !$user->isAdmin()) {
            return $this->response->deny('Вы не можете редактировать этот ресторан');
        }

        return $this->response->allow();
    }

    public function delete(User $user, Restaurant $restaurant): Response
    {
        if (!$user->isAdmin()) {
            return $this->response->deny('Только администратор может удалять рестораны');
        }

        return $this->response->allow();
    }
}
