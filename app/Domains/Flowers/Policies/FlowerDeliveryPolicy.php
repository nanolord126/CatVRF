<?php declare(strict_types=1);

namespace App\Domains\Flowers\Policies;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class FlowerDeliveryPolicy extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    public function view(User $user, FlowerDelivery $delivery): Response
        {
            if ($user->id === $delivery->order->user_id || $user->id === $delivery->shop->user_id) {
                return $this->response->allow();
            }

            return $this->response->deny('You cannot view this delivery');
        }

        public function track(User $user, FlowerDelivery $delivery): Response
        {
            if ($user->id === $delivery->order->user_id) {
                return $this->response->allow();
            }

            return $this->response->deny('You cannot track this delivery');
        }

        public function update(User $user, FlowerDelivery $delivery): Response
        {
            if ($user->id === $delivery->shop->user_id && in_array($delivery->status, ['assigned', 'in_transit'])) {
                return $this->response->allow();
            }

            return $this->response->deny('You cannot update this delivery');
        }
}
