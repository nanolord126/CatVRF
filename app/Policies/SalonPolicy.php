<?php

namespace App\Policies;

use App\Domains\Beauty\Models\BeautySalon;
use App\Models\User;

class SalonPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, BeautySalon $salon): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('create_salons');
    }

    public function update(User $user, BeautySalon $salon): bool
    {
        return $user->tenant_id === $salon->tenant_id && $user->hasPermissionTo('update_salons');
    }

    public function delete(User $user, BeautySalon $salon): bool
    {
        return $user->tenant_id === $salon->tenant_id && $user->hasPermissionTo('delete_salons');
    }
}
