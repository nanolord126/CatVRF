<?php

declare(strict_types=1);

namespace Modules\Warehouse\Infrastructure\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Modules\Warehouse\Domain\Services\LicenseManagementService;
use Modules\Warehouse\Domain\Exceptions\LicenseManagementException;

final class BatchPolicy
{
    use HandlesAuthorization;

    public function __construct(
        private LicenseManagementService $licenseService
    ) {}

    /**
     * Просмотр списка партий
     */
    public function viewAny(User $user): bool
    {
        return $user->can('view batches');
    }

    /**
     * Просмотр конкретной партии
     */
    public function view(User $user, string $batchId): bool
    {
        if (!$user->can('view batches')) {
            return false;
        }

        return $this->checkTenantAccess($user, $batchId);
    }

    /**
     * Создание партии
     */
    public function create(User $user): bool
    {
        if (!$user->can('create batches')) {
            return false;
        }

        // Для медицинских препаратов требуется специальное разрешение
        return $user->can('create pharmaceutical batches');
    }

    /**
     * Обновление партии
     */
    public function update(User $user, string $batchId): bool
    {
        if (!$user->can('edit batches')) {
            return false;
        }

        return $this->checkTenantAccess($user, $batchId);
    }

    /**
     * Списание из партии
     */
    public function deduct(User $user, string $batchId): bool
    {
        if (!$user->can('deduct from batches')) {
            return false;
        }

        return $this->checkTenantAccess($user, $batchId);
    }

    /**
     * Перемещение партии в карантин
     */
    public function quarantine(User $user, string $batchId): bool
    {
        if (!$user->can('quarantine batches')) {
            return false;
        }

        return $this->checkTenantAccess($user, $batchId);
    }

    /**
     * Удаление партии
     */
    public function delete(User $user, string $batchId): bool
    {
        if (!$user->can('delete batches')) {
            return false;
        }

        // Запрет удаления партии с остатками
        // TODO: Проверить current_quantity > 0
        return $this->checkTenantAccess($user, $batchId);
    }

    /**
     * Просмотр просроченных партий
     */
    public function viewExpired(User $user): bool
    {
        if (!$user->can('view batches')) {
            return false;
        }

        return $user->can('view expired batches');
    }

    /**
     * Работа с контролируемыми веществами
     */
    public function handleControlledSubstances(User $user, string $batchId): bool
    {
        if (!$user->can('handle controlled substances')) {
            return false;
        }

        // Дополнительная проверка лицензии
        // TODO: Проверить лицензию на контролируемые вещества

        return $this->checkTenantAccess($user, $batchId);
    }

    /**
     * Проверка доступа к tenant
     */
    private function checkTenantAccess(User $user, string $batchId): bool
    {
        // TODO: Проверить, что партия принадлежит tenant пользователя
        return true;
    }
}
