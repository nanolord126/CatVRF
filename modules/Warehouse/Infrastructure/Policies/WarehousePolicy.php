<?php

declare(strict_types=1);

namespace Modules\Warehouse\Infrastructure\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Modules\Warehouse\Domain\Services\PIIProtectionService;
use Modules\Warehouse\Domain\Exceptions\PIIProtectionException;

final class WarehousePolicy
{
    use HandlesAuthorization;

    public function __construct(
        private PIIProtectionService $piiService
    ) {}

    /**
     * Просмотр списка складов
     */
    public function viewAny(User $user): bool
    {
        return $user->can('view warehouses');
    }

    /**
     * Просмотр конкретного склада
     */
    public function view(User $user, string $warehouseId): bool
    {
        if (!$user->can('view warehouses')) {
            return false;
        }

        // Проверка tenant isolation
        if (!$this->checkTenantAccess($user, $warehouseId)) {
            return false;
        }

        return true;
    }

    /**
     * Создание склада
     */
    public function create(User $user): bool
    {
        return $user->can('create warehouses');
    }

    /**
     * Обновление склада
     */
    public function update(User $user, string $warehouseId): bool
    {
        if (!$user->can('edit warehouses')) {
            return false;
        }

        if (!$this->checkTenantAccess($user, $warehouseId)) {
            return false;
        }

        return true;
    }

    /**
     * Удаление склада
     */
    public function delete(User $user, string $warehouseId): bool
    {
        if (!$user->can('delete warehouses')) {
            return false;
        }

        if (!$this->checkTenantAccess($user, $warehouseId)) {
            return false;
        }

        // Запрет удаления склада с остатками
        // TODO: Проверить current_stock > 0
        return true;
    }

    /**
     * Активация/деактивация склада
     */
    public function toggleActive(User $user, string $warehouseId): bool
    {
        return $this->update($user, $warehouseId);
    }

    /**
     * Просмотр статистики склада
     */
    public function viewStatistics(User $user, string $warehouseId): bool
    {
        if (!$user->can('view warehouse statistics')) {
            return false;
        }

        return $this->checkTenantAccess($user, $warehouseId);
    }

    /**
     * Проверка доступа к tenant
     */
    private function checkTenantAccess(User $user, string $warehouseId): bool
    {
        $warehouse = \Modules\Warehouse\Infrastructure\Repositories\WarehouseRepository::class;
        $repo = app($warehouse);
        
        $warehouse = $repo->findById(
            \Modules\Warehouse\Domain\ValueObjects\WarehouseId::fromString($warehouseId)
        );
        
        if (!$warehouse) {
            return false;
        }
        
        // Проверяем, что склад принадлежит tenant пользователя
        // В реальной реализации это может быть проверка через связь или отдельное поле
        return $warehouse->toArray()['tenant_id'] === $user->tenant_id;
    }
}
