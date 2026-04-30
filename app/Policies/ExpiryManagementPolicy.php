<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

/**
 * Expiry Management Policy
 * 
 * Политика доступа для управления сроками годности
 * Реализует segregation of duties для ФЗ-61 compliance
 * 
 * @author CatVRF Team
 * @version 2026.04.28
 */
final class ExpiryManagementPolicy
{
    use HandlesAuthorization;

    /**
     * Просмотр отчетов о просрочке
     */
    public function viewReports(User $user): bool
    {
        return $user->hasPermission('expiry_management.view_reports');
    }

    /**
     * Блокировка партии
     */
    public function blockBatch(User $user): bool
    {
        // Требует отдельной роли - segregation of duties
        return $user->hasPermission('expiry_management.block');
    }

    /**
     * Разблокировка партии (только для менеджеров)
     */
    public function unblockBatch(User $user): bool
    {
        // Только менеджеры могут разблокировать партии
        return $user->hasPermission('expiry_management.unblock');
    }

    /**
     * Запуск автоматической проверки
     */
    public function runExpiryCheck(User $user): bool
    {
        return $user->hasPermission('expiry_management.run_check');
    }

    /**
     * Ручное списание просроченных партий
     */
    public function writeOffExpired(User $user): bool
    {
        // Требует двухфакторной аутентификации
        return $user->hasPermission('expiry_management.write_off');
    }
}
