<?php

namespace App\Policies;

use App\Models\User;
use App\Domains\Taxi\Models\TaxiRide;
use Illuminate\Auth\Access\HandlesAuthorization;

class TaxiRidePolicy extends BaseSecurityPolicy
{
    use HandlesAuthorization;

    /**
     * Просмотр списка всех поездок (доступно диспетчерам, админам и владельцам).
     */
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'tenant-owner', 'manager', 'dispatcher']);
    }

    /**
     * Просмотр конкретной поездки (водитель видит только свои, диспетчер все в своем тенанте).
     */
    public function view(User $user, TaxiRide $ride): bool
    {
        // Диспетчер/админ видит все поездки тенанта
        if ($user->hasAnyRole(['admin', 'tenant-owner', 'manager', 'dispatcher'])) {
            return $user->tenant_id === $ride->tenant_id;
        }

        // Водитель видит только свои поездки
        if ($user->hasRole('taxi-driver')) {
            return $ride->taxi_driver_id === $user->id;
        }

        return false;
    }

    /**
     * Создание новой поездки (может создавать система, диспетчер или водитель).
     */
    public function create(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'tenant-owner', 'manager', 'dispatcher', 'taxi-driver']) &&
               $user->tenant_id !== null;
    }

    /**
     * Обновление поездки (диспетчер, админ или водитель-владелец).
     */
    public function update(User $user, TaxiRide $ride): bool
    {
        if (!$user->tenant_id || $user->tenant_id !== $ride->tenant_id) {
            return false;
        }

        // Диспетчер/админ может изменять любые поездки
        if ($user->hasAnyRole(['admin', 'tenant-owner', 'manager', 'dispatcher'])) {
            return true;
        }

        // Водитель может изменять только свои поездки в статусе "активна"
        if ($user->hasRole('taxi-driver') && $ride->taxi_driver_id === $user->id) {
            return in_array($ride->status, ['pending', 'accepted']);
        }

        return false;
    }

    /**
     * Отмена поездки (водитель - только свою, диспетчер - любую).
     */
    public function cancel(User $user, TaxiRide $ride): bool
    {
        if ($user->tenant_id !== $ride->tenant_id) {
            return false;
        }

        if ($user->hasRole('admin') || $user->hasRole('tenant-owner')) {
            return true;
        }

        if ($user->hasRole('dispatcher') && $ride->status !== 'completed') {
            return true;
        }

        if ($user->hasRole('taxi-driver') && $ride->taxi_driver_id === $user->id) {
            return in_array($ride->status, ['pending', 'accepted']);
        }

        return false;
    }

    /**
     * Удаление поездки (только админ).
     */
    public function delete(User $user, TaxiRide $ride): bool
    {
        return $user->hasRole('admin') && $user->tenant_id === $ride->tenant_id;
    }

    /**
     * Завершение поездки (система или диспетчер).
     */
    public function complete(User $user, TaxiRide $ride): bool
    {
        if ($user->tenant_id !== $ride->tenant_id) {
            return false;
        }

        return $user->hasAnyRole(['admin', 'tenant-owner', 'manager', 'dispatcher']);
    }

    /**
     * Оценка поездки (пассажир или водитель).
     */
    public function rate(User $user, TaxiRide $ride): bool
    {
        if ($user->tenant_id !== $ride->tenant_id) {
            return false;
        }

        // Водитель может оценить после завершения
        if ($ride->taxi_driver_id === $user->id && $ride->status === 'completed') {
            return true;
        }

        // Пассажир может оценить после завершения (не реализовано в модели, оставляю для расширения)
        return false;
    }
}

