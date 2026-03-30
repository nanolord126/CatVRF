<?php declare(strict_types=1);

namespace App\Domains\Sports\Policies;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class StudioPolicy extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    public function viewAny(?User $user): Response
        {
            return $this->response->allow();
        }

        public function view(?User $user, Studio $studio): Response
        {
            return $this->response->allow();
        }

        public function create(User $user): Response
        {
            return $user->hasPermissionTo('create_studios') ? $this->response->allow() : $this->response->deny();
        }

        public function update(User $user, Studio $studio): Response
        {
            return ($user->id === $studio->owner_id || $user->hasRole('admin')) ? $this->response->allow() : $this->response->deny();
        }

        public function delete(User $user, Studio $studio): Response
        {
            return $user->hasRole('admin') ? $this->response->allow() : $this->response->deny();
        }
}
