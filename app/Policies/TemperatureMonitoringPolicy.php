<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

/**
 * Temperature Monitoring Policy
 * 
 * Политика доступа для мониторинга температурного режима
 * 
 * @author CatVRF Team
 * @version 2026.04.28
 */
final class TemperatureMonitoringPolicy
{
    use HandlesAuthorization;

    /**
     * Просмотр показаний температуры
     */
    public function viewReadings(User $user): bool
    {
        return $user->hasPermission('temperature_monitoring.view');
    }

    /**
     * Запись показаний температуры
     */
    public function recordReading(User $user): bool
    {
        return $user->hasPermission('temperature_monitoring.record');
    }

    /**
     * Просмотр алертов
     */
    public function viewAlerts(User $user): bool
    {
        return $user->hasPermission('temperature_monitoring.view_alerts');
    }

    /**
     * Разрешение алертов
     */
    public function resolveAlert(User $user): bool
    {
        return $user->hasPermission('temperature_monitoring.resolve_alert');
    }

    /**
     * Просмотр статистики
     */
    public function viewStatistics(User $user): bool
    {
        return $user->hasPermission('temperature_monitoring.view_statistics');
    }
}
