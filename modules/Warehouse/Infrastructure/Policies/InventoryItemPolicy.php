<?php

declare(strict_types=1);

namespace Modules\Warehouse\Infrastructure\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

final class InventoryItemPolicy
{
    use HandlesAuthorization;

    /**
     * Просмотр списка запасов
     */
    public function viewAny(User $user): bool
    {
        return $user->can('view inventory');
    }

    /**
     * Просмотр конкретного запаса
     */
    public function view(User $user, string $inventoryItemId): bool
    {
        if (!$user->can('view inventory')) {
            return false;
        }

        return $this->checkTenantAccess($user, $inventoryItemId);
    }

    /**
     * Создание запаса
     */
    public function create(User $user): bool
    {
        return $user->can('create inventory');
    }

    /**
     * Обновление запаса
     */
    public function update(User $user, string $inventoryItemId): bool
    {
        if (!$user->can('edit inventory')) {
            return false;
        }

        return $this->checkTenantAccess($user, $inventoryItemId);
    }

    /**
     * Резервирование товара
     */
    public function reserve(User $user, string $inventoryItemId): bool
    {
        if (!$user->can('reserve inventory')) {
            return false;
        }

        return $this->checkTenantAccess($user, $inventoryItemId);
    }

    /**
     * Снятие резерва
     */
    public function release(User $user, string $inventoryItemId): bool
    {
        return $this->reserve($user, $inventoryItemId);
    }

    /**
     * Удаление запаса
     */
    public function delete(User $user, string $inventoryItemId): bool
    {
        if (!$user->can('delete inventory')) {
            return false;
        }

        return $this->checkTenantAccess($user, $inventoryItemId);
    }

    /**
     * Конвертация типа заказа (B2B/B2C)
     */
    public function convertOrderType(User $user, string $inventoryItemId): bool
    {
        if (!$user->can('convert inventory order type')) {
            return false;
        }

        return $this->checkTenantAccess($user, $inventoryItemId);
    }

    /**
     * Проверка доступа к tenant
     */
    private function checkTenantAccess(User $user, string $inventoryItemId): bool
    {
        // TODO: Проверить, что запас принадлежит tenant пользователя
        return true;
    }
}
