<?php

namespace App\Policies;

use App\Domains\Insurance\Models\InsurancePolicy;
use App\Models\User;

class InsurancePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->tenant_id === tenant()->id;
    }

    public function view(User $user, InsurancePolicy $policy): bool
    {
        return $user->tenant_id === $policy->tenant_id;
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('create_policies');
    }

    public function update(User $user, InsurancePolicy $policy): bool
    {
        return $user->tenant_id === $policy->tenant_id && $user->hasPermissionTo('update_policies');
    }

    public function delete(User $user, InsurancePolicy $policy): bool
    {
        return $user->tenant_id === $policy->tenant_id && $user->hasPermissionTo('delete_policies');
    }
}
