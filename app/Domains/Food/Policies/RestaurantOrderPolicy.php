declare(strict_types=1);

<?php declare(strict_types=1);

namespace App\Domains\Food\Policies;

use App\Models\User;
use App\Domains\Food\Models\RestaurantOrder;
use Illuminate\Auth\Access\Response;

/**
 * Policy для RestaurantOrder.
 * Production 2026.
 */
final class RestaurantOrderPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, RestaurantOrder $order): bool
    {
        return $user->id === $order->client_id || $user->isAdmin();
    }

    public function create(User $user): Response
    {
        if (!$user->isVerified()) {
            return $this->response->deny('Подтвердите аккаунт для создания заказа');
        }

        return $this->response->allow();
    }

    public function cancel(User $user, RestaurantOrder $order): Response
    {
        if ($user->id !== $order->client_id && !$user->isAdmin()) {
            return $this->response->deny('Вы не можете отменить этот заказ');
        }

        if ($order->status === 'cooking' || $order->status === 'ready' || $order->status === 'delivered') {
            return $this->response->deny('Заказ уже готовится или доставляется');
        }

        return $this->response->allow();
    }
}
