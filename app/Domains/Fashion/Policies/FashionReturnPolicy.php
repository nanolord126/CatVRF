<?php declare(strict_types=1);

namespace App\Domains\Fashion\Policies;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class FashionReturnPolicy extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    public function viewAny(User $user): Response
        {
            return $user->hasPermission('view_returns') ? $this->response->allow() : $this->response->deny();
        }

        public function view(User $user, FashionReturn $return): Response
        {
            return $user->id === $return->customer_id || $user->isAdmin() ? $this->response->allow() : $this->response->deny();
        }

        public function create(User $user): Response
        {
            return $user->hasPermission('request_return') ? $this->response->allow() : $this->response->deny();
        }

        public function update(User $user, FashionReturn $return): Response
        {
            return $user->isAdmin() ? $this->response->allow() : $this->response->deny();
        }

        public function delete(User $user, FashionReturn $return): Response
        {
            return $user->isAdmin() ? $this->response->allow() : $this->response->deny();
        }
}
