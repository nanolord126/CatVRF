<?php

namespace App\Policies;

use App\Models\B2BPartner;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class B2BPartnerPolicy extends BaseSecurityPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('view_b2b');
    }

    public function view(User $user, B2BPartner $partner): bool
    {
        return $user->hasPermissionTo('view_b2b');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('manage_b2b');
    }

    public function update(User $user, B2BPartner $partner): bool
    {
        return $user->hasPermissionTo('manage_b2b');
    }

    public function delete(User $user, B2BPartner $partner): bool
    {
        return $user->hasPermissionTo('manage_b2b');
    }
}

