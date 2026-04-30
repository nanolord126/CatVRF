<?php

declare(strict_types=1);

namespace Modules\Warehouse\Infrastructure\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

final class BinPolicy
{
    use HandlesAuthorization;

    /**
     * Просмотр списка ячеек
     */
    public function viewAny(User $user): bool
    {
        return $user->can('view bins');
    }

    /**
     * Просмотр конкретной ячейки
     */
    public function view(User $user, string $binId): bool
    {
        if (!$user->can('view bins')) {
            return false;
        }

        return $this->checkTenantAccess($user, $binId);
    }

    /**
     * Создание ячейки
     */
    public function create(User $user): bool
    {
        return $user->can('create bins');
    }

    /**
     * Обновление ячейки
     */
    public function update(User $user, string $binId): bool
    {
        if (!$user->can('edit bins')) {
            return false;
        }

        return $this->checkTenantAccess($user, $binId);
    }

    /**
     * Удаление ячейки
     */
    public function delete(User $user, string $binId): bool
    {
        if (!$user->can('delete bins')) {
            return false;
        }

        // Запрет удаления ячейки с товарами
        // TODO: Проверить current_stock > 0

        return $this->checkTenantAccess($user, $binId);
    }

    /**
     * Проверка доступа к tenant
     */
    private function checkTenantAccess(User $user, string $binId): bool
    {
        // TODO: Проверить, что ячейка принадлежит tenant пользователя
        return true;
    }
}
