<?php

namespace App\Domains\Delivery\Policies;

use App\Domains\Delivery\Models\DeliveryOrder;
use App\Models\User;

class DeliveryOrderPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->tenant_id === tenant()->id;
    }

    public function view(User $user, DeliveryOrder $order): bool
    {
        return $user->tenant_id === $order->tenant_id;
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('create_orders');
    }

    public function update(User $user, DeliveryOrder $order): bool
    {
        return $user->tenant_id === $order->tenant_id && $user->hasPermissionTo('update_orders');
    }

    public function delete(User $user, DeliveryOrder $order): bool
    {
        return $user->tenant_id === $order->tenant_id && $user->hasPermissionTo('delete_orders');
    }
}
