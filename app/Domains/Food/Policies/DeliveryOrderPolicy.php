<?php declare(strict_types=1);

namespace App\Domains\Food\Policies;

use App\Domains\Food\Models\DeliveryOrder;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

final class DeliveryOrderPolicy
{
    use HandlesAuthorization;

    public function view(User $user, DeliveryOrder $delivery): bool
    {
        // Customer can view their own deliveries
        if ($delivery->order && $delivery->order->customer_id === $user->id) {
            return true;
        }

        // Courier can view deliveries assigned to them
        if ($delivery->courier_id === $user->id) {
            return true;
        }

        // Admin/Staff can view all deliveries in their tenant
        if ($user->isAdmin() || $user->isStaff()) {
            return $delivery->tenant_id === $user->tenant_id;
        }

        return false;
    }

    public function update(User $user, DeliveryOrder $delivery): bool
    {
        // Only courier assigned to delivery can update status
        if ($delivery->courier_id === $user->id) {
            return true;
        }

        // Admin/Staff can update any delivery in their tenant
        if ($user->isAdmin() || $user->isStaff()) {
            return $delivery->tenant_id === $user->tenant_id;
        }

        return false;
    }

    public function start(User $user, DeliveryOrder $delivery): bool
    {
        // Only courier assigned to delivery can start it
        if ($delivery->courier_id === $user->id) {
            return $delivery->status === DeliveryOrder::STATUS_ACCEPTED || $delivery->status === DeliveryOrder::STATUS_PENDING;
        }

        // Admin/Staff can start any delivery in their tenant
        if ($user->isAdmin() || $user->isStaff()) {
            return $delivery->tenant_id === $user->tenant_id;
        }

        return false;
    }

    public function track(User $user, DeliveryOrder $delivery): bool
    {
        // Customer can track their own deliveries
        if ($delivery->order && $delivery->order->customer_id === $user->id) {
            return true;
        }

        // Courier can track deliveries assigned to them
        if ($delivery->courier_id === $user->id) {
            return true;
        }

        // Admin/Staff can track all deliveries in their tenant
        if ($user->isAdmin() || $user->isStaff()) {
            return $delivery->tenant_id === $user->tenant_id;
        }

        return false;
    }

    public function cancel(User $user, DeliveryOrder $delivery): bool
    {
        // Customer can cancel their own pending/accepted deliveries
        if ($delivery->order && $delivery->order->customer_id === $user->id) {
            return in_array($delivery->status, [
                DeliveryOrder::STATUS_PENDING,
                DeliveryOrder::STATUS_ACCEPTED,
            ]);
        }

        // Admin/Staff can cancel any delivery in their tenant
        if ($user->isAdmin() || $user->isStaff()) {
            return $delivery->tenant_id === $user->tenant_id;
        }

        return false;
    }
}
