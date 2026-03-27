<?php

declare(strict_types=1);


namespace App\Domains\Beauty\Policies;

use App\Domains\Beauty\Models\Master;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

/**
 * Политика для мастеров.
 * Production 2026.
 */
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
            $master->tenant_id === tenant('id') && (
                $master->user_id === $user->id || // Сам мастер
                $user->can('update_masters')
            )
        );
    }

    public function delete(User $user, Master $master): bool
    {
        return $master->tenant_id === tenant('id') && $user->can('delete_masters');
    }

    public function restore(User $user, Master $master): bool
    {
        return $master->tenant_id === tenant('id') && $user->can('restore_masters');
    }

    public function forceDelete(User $user, Master $master): bool
    {
        return $master->tenant_id === tenant('id') && $user->can('force_delete_masters');
    }
}
