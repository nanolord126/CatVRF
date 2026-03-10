<?php

declare(strict_types=1);

namespace App\Domains\Food\Policies;

use App\Models\User;
use App\Domains\Food\Models\FoodOrder;
use Illuminate\Auth\Access\HandlesAuthorization;

final class FoodOrderPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'tenant-owner', 'manager', 'restaurant-staff']) &&
               $user->tenant_id !== null;
    }

    public function view(User $user, FoodOrder $order): bool
    {
        if ($user->tenant_id !== $order->tenant_id) {
            return false;
        }

        if ($user->hasAnyRole(['admin', 'tenant-owner', 'manager', 'restaurant-staff'])) {
            return true;
        }

        return $order->user_id === $user->id;
    }

    public function create(User $user): bool
    {
        return $user->tenant_id !== null;
    }

    public function update(User $user, FoodOrder $order): bool
    {
        if ($user->tenant_id !== $order->tenant_id) {
            return false;
        }

        if ($user->hasAnyRole(['admin', 'tenant-owner', 'manager', 'restaurant-staff'])) {
            return in_array($order->status, ['pending', 'confirmed', 'preparing']);
        }

        return $order->user_id === $user->id && $order->status === 'pending';
    }

    public function delete(User $user, FoodOrder $order): bool
    {
        if ($user->tenant_id !== $order->tenant_id) {
            return false;
        }

        return $user->hasAnyRole(['admin', 'tenant-owner', 'manager']) &&
               in_array($order->status, ['pending', 'cancelled']);
    }

    public function restore(User $user, FoodOrder $order): bool
    {
        if ($user->tenant_id !== $order->tenant_id) {
            return false;
        }

        return $user->hasAnyRole(['admin', 'tenant-owner', 'manager']);
    }

    public function forceDelete(User $user, FoodOrder $order): bool
    {
        if ($user->tenant_id !== $order->tenant_id) {
            return false;
        }

        return $user->hasRole('admin');
    }
}
