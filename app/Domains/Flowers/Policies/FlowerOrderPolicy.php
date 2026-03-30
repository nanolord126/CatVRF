<?php declare(strict_types=1);

namespace App\Domains\Flowers\Policies;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class FlowerOrderPolicy extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    public function viewAny(User $user): Response
        {
            return $this->response->allow();
        }

        public function view(User $user, FlowerOrder $order): Response
        {
            if ($user->id === $order->user_id || $user->id === $order->shop->user_id) {
                return $this->response->allow();
            }

            return $this->response->deny('You cannot view this order');
        }

        public function create(User $user): Response
        {
            return $this->response->allow();
        }

        public function update(User $user, FlowerOrder $order): Response
        {
            if ($user->id === $order->shop->user_id && in_array($order->status, ['pending', 'confirmed'])) {
                return $this->response->allow();
            }

            return $this->response->deny('You cannot update this order');
        }

        public function delete(User $user, FlowerOrder $order): Response
        {
            if ($user->id === $order->shop->user_id && $order->status === 'pending') {
                return $this->response->allow();
            }

            return $this->response->deny('You cannot delete this order');
        }
}
