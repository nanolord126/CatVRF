<?php

declare(strict_types=1);


namespace App\Domains\Beauty\Policies;

use App\Domains\Beauty\Models\BeautySalon;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

/**
 * Политика для салона красоты.
 * Production 2026.
 */
final class BeautySalonPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return (bool) filament()->getTenant();
    }

    public function view(User $user, BeautySalon $salon): bool
    {
        return $salon->tenant_id === tenant('id');
    }

    public function create(User $user): bool
    {
        return (bool) filament()->getTenant() && $user->can('create_salons');
    }

    public function update(User $user, BeautySalon $salon): bool
    {
        return $salon->tenant_id === tenant('id') && $user->can('update_salons');
    }

    public function delete(User $user, BeautySalon $salon): bool
    {
        return $salon->tenant_id === tenant('id') && $user->can('delete_salons');
    }

    public function restore(User $user, BeautySalon $salon): bool
    {
        return $salon->tenant_id === tenant('id') && $user->can('restore_salons');
    }

    public function forceDelete(User $user, BeautySalon $salon): bool
    {
        return $salon->tenant_id === tenant('id') && $user->can('force_delete_salons');
    }
}
