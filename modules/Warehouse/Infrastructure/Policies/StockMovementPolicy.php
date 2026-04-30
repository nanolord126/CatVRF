<?php

declare(strict_types=1);

namespace Modules\Warehouse\Infrastructure\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

final class StockMovementPolicy
{
    use HandlesAuthorization;

    /**
     * Просмотр списка движений
     */
    public function viewAny(User $user): bool
    {
        return $user->can('view stock movements');
    }

    /**
     * Просмотр конкретного движения
     */
    public function view(User $user, string $movementId): bool
    {
        if (!$user->can('view stock movements')) {
            return false;
        }

        return $this->checkTenantAccess($user, $movementId);
    }

    /**
     * Создание движения (приход)
     */
    public function create(User $user): bool
    {
        return $user->can('create stock movements');
    }

    /**
     * Приход товара
     */
    public function receipt(User $user): bool
    {
        if (!$user->can('create stock movements')) {
            return false;
        }

        return $user->can('receipt goods');
    }

    /**
     * Расход товара
     */
    public function shipment(User $user): bool
    {
        if (!$user->can('create stock movements')) {
            return false;
        }

        return $user->can('ship goods');
    }

    /**
     * Перемещение между зонами
     */
    public function transfer(User $user): bool
    {
        if (!$user->can('create stock movements')) {
            return false;
        }

        return $user->can('transfer goods');
    }

    /**
     * Корректировка запасов
     */
    public function adjustment(User $user): bool
    {
        if (!$user->can('create stock movements')) {
            return false;
        }

        return $user->can('adjust inventory');
    }

    /**
     * Списание товара (damage, loss)
     */
    public function writeOff(User $user): bool
    {
        if (!$user->can('create stock movements')) {
            return false;
        }

        return $user->can('write off goods');
    }

    /**
     * Отмена движения
     */
    public function cancel(User $user, string $movementId): bool
    {
        if (!$user->can('cancel stock movements')) {
            return false;
        }

        return $this->checkTenantAccess($user, $movementId);
    }

    /**
     * Экспорт движений
     */
    public function export(User $user): bool
    {
        return $user->can('export stock movements');
    }

    /**
     * Проверка доступа к tenant
     */
    private function checkTenantAccess(User $user, string $movementId): bool
    {
        // TODO: Проверить, что движение принадлежит tenant пользователя
        return true;
    }
}
