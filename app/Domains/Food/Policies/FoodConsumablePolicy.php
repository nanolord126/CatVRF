declare(strict_types=1);

<?php declare(strict_types=1);

namespace App\Domains\Food\Policies;

use App\Models\User;
use App\Domains\Food\Models\FoodConsumable;
use Illuminate\Auth\Access\Response;

/**
 * Policy для FoodConsumable (Ингредиент).
 * Production 2026.
 */
final class FoodConsumablePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isStaff();
    }

    public function view(User $user, FoodConsumable $consumable): bool
    {
        return $user->isStaff();
    }

    public function create(User $user): Response
    {
        if (!$user->isStaff()) {
            return $this->response->deny('Только персонал может создавать ингредиенты');
        }

        return $this->response->allow();
    }

    public function update(User $user, FoodConsumable $consumable): Response
    {
        if (!$user->isStaff()) {
            return $this->response->deny('Только персонал может редактировать ингредиенты');
        }

        return $this->response->allow();
    }

    public function delete(User $user, FoodConsumable $consumable): Response
    {
        if (!$user->isAdmin()) {
            return $this->response->deny('Только администратор может удалять ингредиенты');
        }

        return $this->response->allow();
    }
}
