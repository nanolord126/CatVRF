<?php declare(strict_types=1);

namespace App\Domains\Sports\Fitness\Policies;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class TrainerPolicy extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    use HandlesAuthorization;

        public function viewAny(User $user): Response
        {
            return $this->response->allow();
        }

        public function view(User $user, Trainer $trainer): Response
        {
            return $this->response->allow();
        }

        public function create(User $user): Response
        {
            return $user->hasPermissionTo('create_trainers') ? $this->response->allow() : $this->response->deny();
        }

        public function update(User $user, Trainer $trainer): Response
        {
            return $user->id === $trainer->user_id || $user->hasPermissionTo('update_trainers') ? $this->response->allow() : $this->response->deny();
        }

        public function delete(User $user, Trainer $trainer): Response
        {
            return $user->hasPermissionTo('delete_trainers') ? $this->response->allow() : $this->response->deny();
        }
}
