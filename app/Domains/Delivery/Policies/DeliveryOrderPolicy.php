<?php

declare(strict_types=1);

namespace App\Domains\Delivery\Policies;

use App\Models\User;
use App\Domains\Delivery\Models\DeliveryOrder;
use Illuminate\Auth\Access\HandlesAuthorization;

final class DeliveryOrderPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'tenant-owner', 'manager', 'delivery-dispatcher', 'courier']) &&
               $user->tenant_id !== null;
    }

    public function view(User $user, DeliveryOrder $order): bool
    {
        if ($user->tenant_id !== $order->tenant_id) {
            return false;
        }

        if ($user->hasAnyRole(['admin', 'tenant-owner', 'manager', 'delivery-dispatcher'])) {
            return true;
        }

        if ($user->hasRole('courier') && $order->assigned_courier_id === $user->id) {
            return true;
        }

        return $order->user_id === $user->id;
    }

    public function create(User $user): bool
    {
        return $user->tenant_id !== null &&
               $user->hasAnyRole(['admin', 'tenant-owner', 'manager', 'delivery-dispatcher']);
    }

    public function update(User $user, DeliveryOrder $order): bool
    {
        if ($user->tenant_id !== $order->tenant_id) {
            return false;
        }

        if ($user->hasAnyRole(['admin', 'tenant-owner', 'manager', 'delivery-dispatcher'])) {
            return true;
        }

        return $order->assigned_courier_id === $user->id &&
               !in_array($order->status, ['delivered', 'cancelled']);
    }

    public function delete(User $user, DeliveryOrder $order): bool
    {
        if ($user->tenant_id !== $order->tenant_id) {
            return false;
        }

        return $user->hasAnyRole(['admin', 'tenant-owner', 'manager']) &&
               in_array($order->status, ['pending', 'cancelled']);
    }

    public function restore(User $user, DeliveryOrder $order): bool
    {
        if ($user->tenant_id !== $order->tenant_id) {
            return false;
        }

        return $user->hasAnyRole(['admin', 'tenant-owner', 'manager']);
    }

    public function forceDelete(User $user, DeliveryOrder $order): bool
    {
        if ($user->tenant_id !== $order->tenant_id) {
            return false;
        }

        return $user->hasRole('admin');
    }
}
