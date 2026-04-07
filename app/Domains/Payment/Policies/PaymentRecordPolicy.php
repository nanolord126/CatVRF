<?php

declare(strict_types=1);

namespace App\Domains\Payment\Policies;

use App\Domains\Payment\Models\PaymentRecord;
use App\Models\User;

/**
 * Политика доступа к платёжным записям.
 *
 * Tenant-scoped: пользователь видит только свои платежи.
 * B2B: дополнительная изоляция по business_group_id.
 */
final class PaymentRecordPolicy
{
    /**
     * Может ли пользователь просматривать список платежей.
     */
    public function viewAny(User $user): bool
    {
        return $user->tenant_id !== null;
    }

    /**
     * Может ли пользователь просматривать конкретный платёж.
     */
    public function view(User $user, PaymentRecord $record): bool
    {
        if ($user->tenant_id !== $record->tenant_id) {
            return false;
        }

        // B2B: изоляция по филиалу
        if ($user->active_business_group_id !== null
            && $record->business_group_id !== null
            && $user->active_business_group_id !== $record->business_group_id
        ) {
            return false;
        }

        return true;
    }

    /**
     * Может ли пользователь создавать платежи.
     */
    public function create(User $user): bool
    {
        return $user->tenant_id !== null;
    }

    /**
     * Может ли пользователь обновлять платёж.
     */
    public function update(User $user, PaymentRecord $record): bool
    {
        if ($user->tenant_id !== $record->tenant_id) {
            return false;
        }

        // Нельзя менять финальные платежи
        if ($record->isFinal()) {
            return false;
        }

        return true;
    }

    /**
     * Удаление запрещено — платежи не удаляются.
     */
    public function delete(User $user, PaymentRecord $record): bool
    {
        return false;
    }

    /**
     * Восстановление запрещено.
     */
    public function restore(User $user, PaymentRecord $record): bool
    {
        return false;
    }

    /**
     * Принудительное удаление запрещено навсегда.
     */
    public function forceDelete(User $user, PaymentRecord $record): bool
    {
        return false;
    }
}
