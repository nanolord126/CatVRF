<?php

namespace App\Policies;

use App\Models\User;
use App\Domains\Finances\Models\PaymentTransaction;
use Illuminate\Auth\Access\Response;

/**
 * Policy для управления доступом к платежам.
 *
 * Используется для контроля доступа к платежным транзакциям на уровне приложения.
 * Работает с multi-tenancy и проверяет tenant_id для каждого действия.
 */
class PaymentTransactionPolicy
{
    /**
     * Может ли пользователь просматривать список платежей.
     */
    public function viewAny(User $user): Response
    {
        return $user->hasPermissionTo('view-payments')
            ? Response::allow()
            : Response::deny('Нет прав доступа к платежам');
    }

    /**
     * Может ли пользователь просматривать конкретный платёж.
     */
    public function view(User $user, PaymentTransaction $payment): Response
    {
        // Проверка принадлежности платежа тенанту пользователя
        if ($payment->tenant_id !== $user->current_tenant_id) {
            return Response::deny('Платёж принадлежит другому тенанту');
        }

        return Response::allow();
    }

    /**
     * Может ли пользователь создавать платежи.
     */
    public function create(User $user): Response
    {
        return $user->hasPermissionTo('create-payments')
            ? Response::allow()
            : Response::deny('Нет прав создавать платежи');
    }

    /**
     * Может ли пользователь обновлять платёж (статус, метаданные).
     */
    public function update(User $user, PaymentTransaction $payment): Response
    {
        if ($payment->tenant_id !== $user->current_tenant_id) {
            return Response::deny('Платёж принадлежит другому тенанту');
        }

        return $user->hasPermissionTo('edit-payments')
            ? Response::allow()
            : Response::deny('Нет прав редактировать платежи');
    }

    /**
     * Может ли пользователь возвращать платежи.
     */
    public function refund(User $user, PaymentTransaction $payment): Response
    {
        if ($payment->tenant_id !== $user->current_tenant_id) {
            return Response::deny('Платёж принадлежит другому тенанту');
        }

        // Проверка статуса платежа
        if (!$payment->isSuccessful()) {
            return Response::deny('Возврат возможен только для успешных платежей');
        }

        return $user->hasPermissionTo('refund-payments')
            ? Response::allow()
            : Response::deny('Нет прав для возврата платежей');
    }

    /**
     * Может ли пользователь удалять платежи (только администраторы).
     */
    public function delete(User $user, PaymentTransaction $payment): Response
    {
        if ($payment->tenant_id !== $user->current_tenant_id) {
            return Response::deny('Платёж принадлежит другому тенанту');
        }

        return $user->hasPermissionTo('delete-payments')
            ? Response::allow()
            : Response::deny('Нет прав удалять платежи');
    }
}
