<?php

declare(strict_types=1);


namespace App\Domains\Food\Policies;

use App\Models\User;
use App\Domains\Food\Models\Dish;
use Illuminate\Auth\Access\Response;

/**
 * Policy для Dish (Блюдо).
 * Production 2026.
 */
final class DishPolicy
{
    public function viewAny(User $user): bool
    {
        return true; // Все могут видеть меню
    }

    public function view(User $user, Dish $dish): bool
    {
        return true; // Блюдо публичное
    }

    public function create(User $user): Response
    {
        if (!$user->isStaff()) {
            return $this->response->deny('Только персонал может создавать блюда');
        }

        return $this->response->allow();
    }

    public function update(User $user, Dish $dish): Response
    {
        if (!$user->isStaff()) {
            return $this->response->deny('Только персонал может редактировать блюда');
        }

        return $this->response->allow();
    }

    public function delete(User $user, Dish $dish): Response
    {
        if (!$user->isAdmin()) {
            return $this->response->deny('Только администратор может удалять блюда');
        }

        return $this->response->allow();
    }
}
