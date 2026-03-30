<?php declare(strict_types=1);

namespace App\Domains\Food\Policies;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class FoodConsumablePolicy extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
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
