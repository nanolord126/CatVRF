<?php declare(strict_types=1);

namespace App\Domains\Taxi\Policies;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class TaxiDriverPolicy extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
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
                return $this->response->deny('Вы не можете редактировать этого водителя');
            }

            return $this->response->allow();
        }

        public function deactivate(User $user, TaxiDriver $driver): Response
        {
            if (!$user->isAdmin()) {
                return $this->response->deny('Только администратор может деактивировать водителя');
            }

            return $this->response->allow();
        }
}
