<?php declare(strict_types=1);

namespace App\Domains\Fashion\Policies;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class FashionOrderPolicy extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    public function viewAny(User $user): Response
        {
            return $user->hasPermission('view_orders') ? $this->response->allow() : $this->response->deny();
        }

        public function view(User $user, FashionOrder $order): Response
        {
            return $user->id === $order->customer_id || $user->isAdmin() ? $this->response->allow() : $this->response->deny();
        }

        public function create(User $user): Response
        {
            return $user->hasPermission('create_order') ? $this->response->allow() : $this->response->deny();
        }

        public function update(User $user, FashionOrder $order): Response
        {
            return $user->id === $order->customer_id || $user->isAdmin() ? $this->response->allow() : $this->response->deny();
        }

        public function delete(User $user, FashionOrder $order): Response
        {
            return $user->isAdmin() ? $this->response->allow() : $this->response->deny();
        }
}
