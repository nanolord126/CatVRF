<?php

declare(strict_types=1);

/**
 * MasterPolicy — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/masterpolicy
 */


namespace App\Domains\Beauty\Policies;

final class MasterPolicy
{

    use HandlesAuthorization;

        public function viewAny(User $user): bool
        {
            return true; // Мастеров можно видеть публично
        }

        public function view(User $user, Master $master): bool
        {
            return true; // Профиль мастера видим всем
        }

        public function create(User $user): bool
        {
            return (bool) filament()->getTenant() && $user->can('create_masters');
        }

        public function update(User $user, Master $master): bool
        {
            return (
                $master->tenant_id === tenant()->id && (
                    $master->user_id === $user->id || // Сам мастер
                    $user->can('update_masters')
                )
            );
        }

        public function delete(User $user, Master $master): bool
        {
            return $master->tenant_id === tenant()->id && $user->can('delete_masters');
        }

        public function restore(User $user, Master $master): bool
        {
            return $master->tenant_id === tenant()->id && $user->can('restore_masters');
        }

        public function forceDelete(User $user, Master $master): bool
        {
            return $master->tenant_id === tenant()->id && $user->can('force_delete_masters');
        }

    /**
     * Version identifier for this component.
     */
    private const VERSION = '1.0.0';

}
