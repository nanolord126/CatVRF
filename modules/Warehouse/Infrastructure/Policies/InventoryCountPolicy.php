<?php

declare(strict_types=1);

namespace Modules\Warehouse\Infrastructure\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

final class InventoryCountPolicy
{
    use HandlesAuthorization;

    /**
     * Просмотр списка инвентаризаций
     */
    public function viewAny(User $user): bool
    {
        return $user->can('view inventory counts');
    }

    /**
     * Просмотр конкретной инвентаризации
     */
    public function view(User $user, string $inventoryCountId): bool
    {
        if (!$user->can('view inventory counts')) {
            return false;
        }

        return $this->checkTenantAccess($user, $inventoryCountId);
    }

    /**
     * Создание инвентаризации
     */
    public function create(User $user): bool
    {
        return $user->can('create inventory counts');
    }

    /**
     * Запуск инвентаризации
     */
    public function start(User $user, string $inventoryCountId): bool
    {
        if (!$user->can('start inventory counts')) {
            return false;
        }

        return $this->checkTenantAccess($user, $inventoryCountId);
    }

    /**
     * Добавление позиции в инвентаризацию
     */
    public function addItem(User $user, string $inventoryCountId): bool
    {
        if (!$user->can('edit inventory counts')) {
            return false;
        }

        return $this->checkTenantAccess($user, $inventoryCountId);
    }

    /**
     * Завершение инвентаризации
     */
    public function complete(User $user, string $inventoryCountId): bool
    {
        if (!$user->can('complete inventory counts')) {
            return false;
        }

        return $this->checkTenantAccess($user, $inventoryCountId);
    }

    /**
     * Утверждение инвентаризации
     */
    public function approve(User $user, string $inventoryCountId): bool
    {
        if (!$user->can('approve inventory counts')) {
            return false;
        }

        // Segregation of duties: утверждение может делать только менеджер
        return $user->hasRole(['manager', 'admin']);
    }

    /**
     * Отмена инвентаризации
     */
    public function cancel(User $user, string $inventoryCountId): bool
    {
        if (!$user->can('cancel inventory counts')) {
            return false;
        }

        return $this->checkTenantAccess($user, $inventoryCountId);
    }

    /**
     * Экспорт результатов инвентаризации
     */
    public function export(User $user, string $inventoryCountId): bool
    {
        if (!$user->can('export inventory counts')) {
            return false;
        }

        return $this->checkTenantAccess($user, $inventoryCountId);
    }

    /**
     * Проверка доступа к tenant
     */
    private function checkTenantAccess(User $user, string $inventoryCountId): bool
    {
        // TODO: Проверить, что инвентаризация принадлежит tenant пользователя
        return true;
    }
}
