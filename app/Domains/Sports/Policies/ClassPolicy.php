<?php declare(strict_types=1);

namespace App\Domains\Sports\Policies;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class ClassPolicy extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    public function viewAny(?User $user): Response
        {
            return $this->response->allow();
        }

        public function view(?User $user, ClassSession $class): Response
        {
            return $this->response->allow();
        }

        public function create(User $user): Response
        {
            return $user->hasPermissionTo('create_classes') ? $this->response->allow() : $this->response->deny();
        }

        public function update(User $user, ClassSession $class): Response
        {
            return ($user->id === $class->trainer->user_id || $user->hasRole('admin')) ? $this->response->allow() : $this->response->deny();
        }

        public function delete(User $user, ClassSession $class): Response
        {
            return $user->hasRole('admin') ? $this->response->allow() : $this->response->deny();
        }
}
