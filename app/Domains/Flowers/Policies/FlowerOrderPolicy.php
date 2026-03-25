declare(strict_types=1);

<?php declare(strict_types=1);

namespace App\Domains\Flowers\Policies;

use App\Domains\Flowers\Models\FlowerOrder;
use App\Models\User;
use Illuminate\Auth\Access\Response;

final /**
 * FlowerOrderPolicy
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class FlowerOrderPolicy
{
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
