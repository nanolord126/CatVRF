<?php

namespace App\Policies;

use App\Models\User;
use App\Domains\Food\Models\FoodOrder;
use Illuminate\Auth\Access\HandlesAuthorization;

/**
 * FoodOrderPolicy - Правила доступа к заказам (Production 2026).
 *
 * @package App\Policies
 */
class FoodOrderPolicy extends BaseSecurityPolicy
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

        // Персонал ресторана может видеть все заказы для этого ресторана
        if ($user->hasAnyRole(['admin', 'tenant-owner', 'manager', 'restaurant-staff'])) {
            return true;
        }

        // Клиент может видеть только свои заказы
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

        // Персонал может изменять заказы в статусе pending/confirmed
        if ($user->hasAnyRole(['admin', 'tenant-owner', 'manager', 'restaurant-staff'])) {
            return in_array($order->status, ['pending', 'confirmed']);
        }

        // Клиент может изменять только свои неподтвержденные заказы
        return $order->user_id === $user->id && $order->status === 'pending';
    }

    public function cancel(User $user, FoodOrder $order): bool
    {
        if ($user->tenant_id !== $order->tenant_id) {
            return false;
        }

        // Персонал может отменить заказ до статуса ready
        if ($user->hasAnyRole(['admin', 'tenant-owner', 'manager', 'restaurant-staff'])) {
            return in_array($order->status, ['pending', 'confirmed']);
        }

        // Клиент может отменить свой заказ до готовности
        return $order->user_id === $user->id && in_array($order->status, ['pending', 'confirmed']);
    }

    public function delete(User $user, FoodOrder $order): bool
    {
        return $user->hasRole('admin') && $user->tenant_id === $order->tenant_id;
    }

    public function confirm(User $user, FoodOrder $order): bool
    {
        if ($user->tenant_id !== $order->tenant_id) {
            return false;
        }

        return $user->hasAnyRole(['admin', 'tenant-owner', 'manager', 'restaurant-staff']);
    }

    public function markReady(User $user, FoodOrder $order): bool
    {
        if ($user->tenant_id !== $order->tenant_id) {
            return false;
        }

        return $user->hasAnyRole(['admin', 'tenant-owner', 'manager', 'restaurant-staff']);
    }

    public function complete(User $user, FoodOrder $order): bool
    {
        if ($user->tenant_id !== $order->tenant_id) {
            return false;
        }

        return $user->hasAnyRole(['admin', 'tenant-owner', 'manager', 'restaurant-staff']);
    }
}
