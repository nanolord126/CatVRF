<?php

namespace App\Domains\Insurance\Policies;

use App\Models\User;
use App\Domains\Insurance\Models\InsurancePolicy;

class InsurancePolicyPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, InsurancePolicy $policy): bool
    {
        return $user->tenant_id === $policy->tenant_id;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, InsurancePolicy $policy): bool
    {
        return $user->id === $policy->policyholder_id && $user->tenant_id === $policy->tenant_id;
    }

    public function delete(User $user, InsurancePolicy $policy): bool
    {
        return $user->id === $policy->policyholder_id && $user->tenant_id === $policy->tenant_id;
    }
}
