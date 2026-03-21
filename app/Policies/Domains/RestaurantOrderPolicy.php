<?php declare(strict_types=1);

namespace App\Policies\Domains;

use App\Domains\Food\Models\RestaurantOrder;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

final class RestaurantOrderPolicy
{
    use HandlesAuthorization;

    /**
     * Admins can do anything
     */
    public function before(User $user, string $ability): bool|null
    {
        if ($user->hasRole('admin')) {
            return true;
        }

        return null;
    }

    /**
     * View order (customer, restaurant staff, manager, admin)
     */
    public function view(User $user, RestaurantOrder $order): bool
    {
        // Tenant scoping
        if ($order->tenant_id !== $user->tenant_id) {
            return false;
        }

        // Customer can view their own orders
        if ($user->id === $order->customer_id) {
            return true;
        }

        // Restaurant staff and managers can view all orders for their restaurant
        if ($order->restaurant_id === $user->current_business_group_id) {
            return $user->hasRole(['employee', 'manager']);
        }

        // Manager/accountant can view all
        return $user->hasRole(['manager', 'accountant']);
    }

    /**
     * Create order (customer)
     */
    public function create(User $user): bool
    {
        return $user->hasRole(['customer', 'business_owner', 'manager']);
    }

    /**
     * Update order status (restaurant staff only)
     */
    public function updateStatus(User $user, RestaurantOrder $order): bool
    {
        if ($order->tenant_id !== $user->tenant_id) {
            return false;
        }

        // Restaurant staff can update their own restaurant's orders
        if ($order->restaurant_id === $user->current_business_group_id) {
            return $user->hasRole(['employee', 'manager']);
        }

        return false;
    }

    /**
     * Accept order (restaurant staff)
     */
    public function accept(User $user, RestaurantOrder $order): bool
    {
        if ($order->tenant_id !== $user->tenant_id) {
            return false;
        }

        return $order->restaurant_id === $user->current_business_group_id &&
               $user->hasRole(['employee', 'manager']) &&
               $order->status === 'pending';
    }

    /**
     * Mark as ready (restaurant staff)
     */
    public function markReady(User $user, RestaurantOrder $order): bool
    {
        if ($order->tenant_id !== $user->tenant_id) {
            return false;
        }

        return $order->restaurant_id === $user->current_business_group_id &&
               $user->hasRole(['employee', 'manager']) &&
               $order->status === 'cooking';
    }

    /**
     * Complete order (courier, restaurant staff, manager)
     */
    public function complete(User $user, RestaurantOrder $order): bool
    {
        if ($order->tenant_id !== $user->tenant_id) {
            return false;
        }

        // Courier can complete delivery orders
        if ($user->hasRole('courier') && $order->is_delivery) {
            return true;
        }

        // Restaurant staff can complete pickup orders
        if ($order->restaurant_id === $user->current_business_group_id) {
            return $user->hasRole(['employee', 'manager']) && !$order->is_delivery;
        }

        return false;
    }

    /**
     * Cancel order (customer before restaurant accepts, restaurant after with reason, manager/admin)
     */
    public function cancel(User $user, RestaurantOrder $order): bool
    {
        if ($order->tenant_id !== $user->tenant_id) {
            return false;
        }

        // Customer can cancel before restaurant accepts
        if ($user->id === $order->customer_id && $order->status === 'pending') {
            return true;
        }

        // Restaurant can cancel after acceptance (with refund)
        if ($order->restaurant_id === $user->current_business_group_id && $user->hasRole(['manager'])) {
            return in_array($order->status, ['pending', 'accepted', 'cooking']);
        }

        // Manager/admin can cancel
        return $user->hasRole(['manager', 'admin']);
    }

    /**
     * Rate order (customer after completion)
     */
    public function rate(User $user, RestaurantOrder $order): bool
    {
        if ($order->tenant_id !== $user->tenant_id) {
            return false;
        }

        return $user->id === $order->customer_id && $order->status === 'completed';
    }

    /**
     * Delete order (admin only)
     */
    public function delete(User $user, RestaurantOrder $order): bool
    {
        if ($order->tenant_id !== $user->tenant_id) {
            return false;
        }

        return $user->hasRole('admin');
    }

    /**
     * View order kitchen display system
     */
    public function viewKDS(User $user, RestaurantOrder $order): bool
    {
        if ($order->tenant_id !== $user->tenant_id) {
            return false;
        }

        // Only restaurant staff for their own restaurant
        return $order->restaurant_id === $user->current_business_group_id &&
               $user->hasRole(['employee', 'manager']);
    }
}
