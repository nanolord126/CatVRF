<?php

namespace App\Policies;

use App\Domains\RealEstate\Models\Property;
use App\Models\User;

class PropertyPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Property $property): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('create_properties');
    }

    public function update(User $user, Property $property): bool
    {
        return $user->tenant_id === $property->tenant_id && $user->hasPermissionTo('update_properties');
    }

    public function delete(User $user, Property $property): bool
    {
        return $user->tenant_id === $property->tenant_id && $user->hasPermissionTo('delete_properties');
    }
}
