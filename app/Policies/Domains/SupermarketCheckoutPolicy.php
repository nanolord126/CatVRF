<?php

declare(strict_types=1);

namespace App\Policies\Domains;

use App\Domains\Supermarket\Models\SupermarketOrder;
use App\Models\User;

/**
 * Policy for Supermarket checkout operations.
 * Enforces authorization rules for checkout, order creation, and management.
 */
final class SupermarketCheckoutPolicy
{
    /**
     * Admins can do anything.
     */
    public function before(User $user, string $ability): ?bool
    {
        if ($user->hasRole('admin')) {
            return true;
        }

        return null;
    }

    /**
     * Prepare checkout (authenticated users).
     */
    public function prepareCheckout(User $user): bool
    {
        return $user->hasRole(['customer', 'business_owner', 'manager']);
    }

    /**
     * Create order (authenticated users).
     */
    public function createOrder(User $user): bool
    {
        return $user->hasRole(['customer', 'business_owner', 'manager']);
    }

    /**
     * View order (customer can view their own orders, staff can view tenant orders).
     */
    public function view(User $user, SupermarketOrder $order): bool
    {
        // Tenant scoping
        if ($order->tenant_id && $order->tenant_id !== $user->tenant_id) {
            return false;
        }

        // Customer can view their own orders
        if ($user->id === $order->user_id) {
            return true;
        }

        // Staff/managers can view all orders in their tenant
        return $user->hasRole(['manager', 'employee']);
    }

    /**
     * Cancel order (customer can cancel their own pending orders, staff can cancel with reason).
     */
    public function cancelOrder(User $user, SupermarketOrder $order): bool
    {
        // Tenant scoping
        if ($order->tenant_id && $order->tenant_id !== $user->tenant_id) {
            return false;
        }

        // Customer can cancel their own pending/paid orders
        if ($user->id === $order->user_id) {
            return in_array($order->status, ['pending', 'paid'], true);
        }

        // Staff/managers can cancel any order
        return $user->hasRole(['manager', 'employee']);
    }

    /**
     * Confirm payment (customer can confirm their own orders).
     */
    public function confirmPayment(User $user, SupermarketOrder $order): bool
    {
        if ($order->tenant_id && $order->tenant_id !== $user->tenant_id) {
            return false;
        }

        return $user->id === $order->user_id && $order->status === 'pending';
    }

    /**
     * Create reservation (authenticated users).
     */
    public function createReservation(User $user): bool
    {
        return $user->hasRole(['customer', 'business_owner', 'manager']);
    }

    /**
     * Extend reservation (B2B customers only).
     */
    public function extendReservation(User $user): bool
    {
        return $user->hasRole(['business_owner', 'manager']);
    }

    /**
     * Release reservation (customer can release their own reservations).
     */
    public function releaseReservation(User $user): bool
    {
        return $user->hasRole(['customer', 'business_owner', 'manager']);
    }

    /**
     * View cold chain status (customer can view their own orders, staff can view all).
     */
    public function viewColdChainStatus(User $user, SupermarketOrder $order): bool
    {
        if ($order->tenant_id && $order->tenant_id !== $user->tenant_id) {
            return false;
        }

        return $user->id === $order->user_id || $user->hasRole(['manager', 'employee']);
    }

    /**
     * Admin operations (release expired reservations, stats).
     */
    public function adminOperations(User $user): bool
    {
        return $user->hasRole(['admin', 'manager']);
    }
}
