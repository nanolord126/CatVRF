<?php declare(strict_types=1);

namespace Modules\Fashion\Policies;

use App\Models\User;
use App\Domains\Fashion\Models\FashionOrder;

final class FashionOrderPolicy
{
    /**
     * Determine if the user can view any orders.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('view_any_fashion_order') || $user->can('view_own_fashion_order');
    }

    /**
     * Determine if the user can view the order.
     */
    public function view(User $user, FashionOrder $order): bool
    {
        return $user->id === $order->user_id || $user->can('view_any_fashion_order');
    }

    /**
     * Determine if the user can create orders.
     */
    public function create(User $user): bool
    {
        return $user->can('create_fashion_order');
    }

    /**
     * Determine if the user can update the order.
     */
    public function update(User $user, FashionOrder $order): bool
    {
        return $user->id === $order->user_id || $user->can('update_any_fashion_order');
    }

    /**
     * Determine if the user can cancel the order.
     */
    public function cancel(User $user, FashionOrder $order): bool
    {
        return $user->id === $order->user_id && $order->status === 'pending';
    }

    /**
     * Determine if the user can update the order status.
     */
    public function updateStatus(User $user, FashionOrder $order): bool
    {
        return $user->can('update_any_fashion_order_status');
    }
}
