<?php declare(strict_types=1);

namespace App\Domains\Sports\Fitness\Policies;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class FitnessClassPolicy extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    use HandlesAuthorization;

        public function viewAny(User $user): Response
        {
            return $this->response->allow();
        }

        public function view(User $user, FitnessClass $class): Response
        {
            return $this->response->allow();
        }

        public function create(User $user): Response
        {
            return $user->hasPermissionTo('create_classes') ? $this->response->allow() : $this->response->deny();
        }

        public function update(User $user, FitnessClass $class): Response
        {
            return $user->hasPermissionTo('update_classes') ? $this->response->allow() : $this->response->deny();
        }

        public function delete(User $user, FitnessClass $class): Response
        {
            return $user->hasPermissionTo('delete_classes') ? $this->response->allow() : $this->response->deny();
        }
}
