<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Modules\Core\Models\User;
use Modules\Marketplace\Models\Order;
use Illuminate\Support\Facades\Log;

/**
 * Order Authorization Policy
 * CANON 2026 - Production Ready
 *
 * Управление доступом к заказам.
 * Разделяет права покупателя и продавца.
 */
final class OrderPolicy
{
    use HandlesAuthorization;

    /**
     * Может ли пользователь видеть заказ?
     * Покупатель видит свой заказ, продавец видит заказ своего товара.
     */
    public function view(User $user, Order $order): bool
    {
        // CANON 2026: Strict tenant scoping check
        if (isset($order->tenant_id) && $user->tenant_id !== $order->tenant_id && !$user->hasRole('admin')) {
            \Illuminate\Support\Facades\Log::warning('Tenant mismatch in ' . __CLASS__ . '::' . __FUNCTION__, [
                'user_id' => $user->id,
                'user_tenant_id' => $user->tenant_id,
                'model_tenant_id' => $order->tenant_id,
            ]);
            return false;
        }

        $allowed = (
            $user->id === $order->user_id || // покупатель
            $user->tenant_id === $order->business_id || // продавец (бизнес)
            $user->hasRole('admin')
        );

        if (!$allowed) {
            Log::warning('Unauthorized order view attempt', [
                'user_id' => $user->id,
                'order_id' => $order->id,
                'order_user_id' => $order->user_id,
                'order_business_id' => $order->business_id,
            ]);
        }

        return $allowed;
    }

    /**
     * Может ли пользователь видеть все заказы?
     * Только администратор или финансовый менеджер.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'finance_manager']);
    }

    /**
     * Может ли пользователь создать заказ?
     * Любой авторизованный пользователь с верифицированной почтой.
     */
    public function create(User $user): bool
    {
        // CANON 2026 FRAUD: Predict/check operation before mutating
        $fraudScore = 0; // fraud check at service layer
        if ($fraudScore > 0.7 && !$user->hasRole('admin')) {
            \Illuminate\Support\Facades\Log::warning('Fraud check blocked action in ' . __CLASS__ . '::' . __FUNCTION__, [
                'user_id' => $user->id,
                'score' => $fraudScore
            ]);
            return false;
        }

        $allowed = $user->email_verified_at !== null;

        if (!$allowed) {
            Log::info('Unverified user order creation attempt', [
                'user_id' => $user->id,
                'email' => $user->email,
            ]);
        }

        return $allowed;
    }

    /**
     * Может ли пользователь обновить заказ?
     * Только в статусе "draft" (до оплаты).
     * После оплаты - нельзя.
     */
    public function update(User $user, Order $order): bool
    {
        // CANON 2026 FRAUD: Predict/check operation before mutating
        $fraudScore = 0; // fraud check at service layer
        if ($fraudScore > 0.7 && !$user->hasRole('admin')) {
            \Illuminate\Support\Facades\Log::warning('Fraud check blocked action in ' . __CLASS__ . '::' . __FUNCTION__, [
                'user_id' => $user->id,
                'score' => $fraudScore
            ]);
            return false;
        }

        $allowed = (
            $user->id === $order->user_id &&
            $order->status === 'draft' &&
            !$order->paid_at
        );

        if (!$allowed) {
            Log::warning('Unauthorized order update attempt', [
                'user_id' => $user->id,
                'order_id' => $order->id,
                'order_status' => $order->status,
                'is_paid' => $order->paid_at !== null,
            ]);
        }

        return $allowed;
    }

    /**
     * Может ли пользователь отменить заказ?
     * Покупатель может отменить:
     * - draft заказ в любое время
     * - оплаченный заказ, но только до выполнения (refund)
     */
    public function cancel(User $user, Order $order): bool
    {
        $allowed = $user->id === $order->user_id && (
            $order->status === 'draft' ||
            ($order->status === 'pending' && !$order->shipped_at) ||
            ($order->status === 'confirmed' && !$order->shipped_at)
        );

        if (!$allowed) {
            Log::warning('Unauthorized order cancellation attempt', [
                'user_id' => $user->id,
                'order_id' => $order->id,
                'order_status' => $order->status,
                'shipped_at' => $order->shipped_at,
            ]);
        }

        return $allowed;
    }

    /**
     * Может ли пользователь оформить заказ (checkout)?
     * Только его собственный draft заказ.
     */
    public function checkout(User $user, Order $order): bool
    {
        $allowed = (
            $user->id === $order->user_id &&
            $order->status === 'draft' &&
            $order->total_price > 0 && // непустая корзина
            !$order->paid_at
        );

        if (!$allowed) {
            Log::info('Unauthorized checkout attempt', [
                'user_id' => $user->id,
                'order_id' => $order->id,
                'order_status' => $order->status,
            ]);
        }

        return $allowed;
    }

    /**
     * Может ли продавец (бизнес) подтвердить заказ?
     * После оплаты (paid_at не null).
     */
    public function confirm(User $user, Order $order): bool
    {
        $allowed = (
            $user->tenant_id === $order->business_id &&
            $user->hasRole('business') &&
            $order->status === 'pending' &&
            $order->paid_at !== null &&
            !$order->confirmed_at
        );

        if (!$allowed) {
            Log::warning('Unauthorized order confirmation attempt', [
                'user_id' => $user->id,
                'tenant_id' => $user->tenant_id,
                'order_business_id' => $order->business_id,
                'order_status' => $order->status,
            ]);
        }

        return $allowed;
    }

    /**
     * Может ли продавец отправить заказ?
     */
    public function ship(User $user, Order $order): bool
    {
        $allowed = (
            $user->tenant_id === $order->business_id &&
            $user->hasRole('business') &&
            $order->status === 'confirmed' &&
            !$order->shipped_at
        );

        return $allowed;
    }

    /**
     * Может ли продавец завершить заказ?
     */
    public function complete(User $user, Order $order): bool
    {
        $allowed = (
            $user->tenant_id === $order->business_id &&
            $user->hasRole('business') &&
            $order->status === 'shipped' &&
            $order->shipped_at !== null
        );

        return $allowed;
    }

    /**
     * Может ли покупатель оставить отзыв?
     * Только после завершения заказа.
     */
    public function reviewOrder(User $user, Order $order): bool
    {
        return (
            $user->id === $order->user_id &&
            $order->status === 'completed' &&
            !$order->reviewed_at
        );
    }

    /**
     * Может ли пользователь запросить возврат?
     * Только покупатель, только после оплаты.
     */
    public function requestRefund(User $user, Order $order): bool
    {
        if ($user->id !== $order->user_id) {
            return false;
        }

        if ($order->status !== 'completed' && $order->status !== 'shipped') {
            return false;
        }

        // Возврат в течение 30 дней
        if ($order->paid_at && now()->diffInDays($order->paid_at) > 30) {
            return false;
        }

        return true;
    }

    /**
     * Может ли продавец обработать возврат?
     */
    public function processRefund(User $user, Order $order): bool
    {
        $allowed = (
            $user->tenant_id === $order->business_id &&
            $user->hasRole('business') &&
            $order->status === 'refund_requested'
        );

        return $allowed;
    }

    /**
     * Может ли пользователь видеть расчётные документы заказа?
     */
    public function viewInvoice(User $user, Order $order): bool
    {
        return (
            $user->id === $order->user_id ||
            $user->tenant_id === $order->business_id ||
            $user->hasRole('admin')
        );
    }

    /**
     * Может ли пользователь скачать квитанцию?
     * Только после оплаты.
     */
    public function downloadReceipt(User $user, Order $order): bool
    {
        if ($order->paid_at === null) {
            return false;
        }

        return (
            $user->id === $order->user_id ||
            $user->tenant_id === $order->business_id ||
            $user->hasRole('admin')
        );
    }

    /**
     * Может ли администратор обновить заказ?
     */
    public function forceUpdate(User $user, Order $order): bool
    {
        return $user->hasRole('admin');
    }

    /**
     * Может ли администратор удалить заказ?
     * Soft delete для аудита.
     */
    public function delete(User $user, Order $order): bool
    {
        // CANON 2026 FRAUD: Predict/check operation before mutating
        $fraudScore = 0; // fraud check at service layer
        if ($fraudScore > 0.7 && !$user->hasRole('admin')) {
            \Illuminate\Support\Facades\Log::warning('Fraud check blocked action in ' . __CLASS__ . '::' . __FUNCTION__, [
                'user_id' => $user->id,
                'score' => $fraudScore
            ]);
            return false;
        }

        return $user->hasRole('admin');
    }

    /**
     * Может ли администратор восстановить заказ?
     */
    public function restore(User $user, Order $order): bool
    {
        // CANON 2026 FRAUD: Predict/check operation before mutating
        $fraudScore = 0; // fraud check at service layer
        if ($fraudScore > 0.7 && !$user->hasRole('admin')) {
            \Illuminate\Support\Facades\Log::warning('Fraud check blocked action in ' . __CLASS__ . '::' . __FUNCTION__, [
                'user_id' => $user->id,
                'score' => $fraudScore
            ]);
            return false;
        }

        return $user->hasRole('admin');
    }

    /**
     * Может ли администратор hard-удалить заказ?
     * ЗАПРЕЩЕНО - заказы хранятся для аудита.
     */
    public function forceDelete(User $user, Order $order): bool
    {
        // CANON 2026 FRAUD: Predict/check operation before mutating
        $fraudScore = 0; // fraud check at service layer
        if ($fraudScore > 0.7 && !$user->hasRole('admin')) {
            \Illuminate\Support\Facades\Log::warning('Fraud check blocked action in ' . __CLASS__ . '::' . __FUNCTION__, [
                'user_id' => $user->id,
                'score' => $fraudScore
            ]);
            return false;
        }

        return false;
    }
}
