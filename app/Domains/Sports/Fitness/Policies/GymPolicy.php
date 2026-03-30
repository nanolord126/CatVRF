<?php declare(strict_types=1);

namespace App\Domains\Sports\Fitness\Policies;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class GymPolicy extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    use HandlesAuthorization;

        public function viewAny(User $user): Response
        {
            return $this->response->allow();
        }

        public function view(User $user, Gym $gym): Response
        {
            return $this->response->allow();
        }

        public function create(User $user): Response
        {
            return $user->hasPermissionTo('create_gyms') ? $this->response->allow() : $this->response->deny();
        }

        public function update(User $user, Gym $gym): Response
        {
            return $user->hasPermissionTo('update_gyms') ? $this->response->allow() : $this->response->deny();
        }

        public function delete(User $user, Gym $gym): Response
        {
            return $user->hasPermissionTo('delete_gyms') ? $this->response->allow() : $this->response->deny();
        }
}
