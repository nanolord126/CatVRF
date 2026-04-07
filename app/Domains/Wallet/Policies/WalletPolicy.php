<?php

declare(strict_types=1);

namespace App\Domains\Wallet\Policies;

use App\Domains\Wallet\Models\Wallet;
use App\Models\User;

/**
 * Политика доступа к кошелькам.
 *
 * CANON 2026: tenant-scoped + B2B business_group_id isolation.
 * forceDelete запрещён всегда.
 */
final class WalletPolicy
{
    /** Просмотр списка кошельков — только своего tenant. */
    public function viewAny(User $user): bool
    {
        return $user->tenant_id !== null;
    }

    /** Просмотр конкретного кошелька — проверка tenant + business_group. */
    public function view(User $user, Wallet $wallet): bool
    {
        return $this->isSameTenant($user, $wallet)
            && $this->isAccessibleByBusinessGroup($user, $wallet);
    }

    /** Создание кошелька — только аутентифицированные пользователи с tenant. */
    public function create(User $user): bool
    {
        return $user->tenant_id !== null;
    }

    /** Обновление — проверка tenant + business_group. */
    public function update(User $user, Wallet $wallet): bool
    {
        return $this->isSameTenant($user, $wallet)
            && $this->isAccessibleByBusinessGroup($user, $wallet);
    }

    /** Мягкое удаление — проверка tenant + business_group + баланс = 0. */
    public function delete(User $user, Wallet $wallet): bool
    {
        return $this->isSameTenant($user, $wallet)
            && $this->isAccessibleByBusinessGroup($user, $wallet)
            && $wallet->current_balance === 0
            && $wallet->hold_amount === 0;
    }

    /** Восстановление — проверка tenant. */
    public function restore(User $user, Wallet $wallet): bool
    {
        return $this->isSameTenant($user, $wallet);
    }

    /** Полное удаление запрещено всегда (финансовые данные). */
    public function forceDelete(User $user, Wallet $wallet): bool
    {
        return false;
    }

    /** Проверка принадлежности к одному tenant. */
    private function isSameTenant(User $user, Wallet $wallet): bool
    {
        return $user->tenant_id === $wallet->tenant_id;
    }

    /**
     * Проверка B2B business_group изоляции.
     *
     * Если кошелёк привязан к business_group, пользователь должен принадлежать
     * к тому же business_group (или быть owner tenant'а — business_group_id = null).
     */
    private function isAccessibleByBusinessGroup(User $user, Wallet $wallet): bool
    {
        if ($wallet->business_group_id === null) {
            return true;
        }

        $userBusinessGroupId = $user->active_business_group_id ?? $user->business_group_id ?? null;

        if ($userBusinessGroupId === null) {
            return true;
        }

        return $userBusinessGroupId === $wallet->business_group_id;
    }
}
