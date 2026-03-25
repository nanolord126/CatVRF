declare(strict_types=1);

<?php declare(strict_types=1);

namespace App\Domains\Flowers\Policies;

use App\Domains\Flowers\Models\FlowerDelivery;
use App\Models\User;
use Illuminate\Auth\Access\Response;

final /**
 * FlowerDeliveryPolicy
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class FlowerDeliveryPolicy
{
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
