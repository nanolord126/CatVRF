<?php

namespace App\Domains\RealEstate\Policies;

use App\Models\User;
use App\Domains\RealEstate\Models\Property;

class PropertyPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Property $property): bool
    {
        return $user->tenant_id === $property->tenant_id;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Property $property): bool
    {
        return $user->id === $property->owner_id && $user->tenant_id === $property->tenant_id;
    }

    public function delete(User $user, Property $property): bool
    {
        return $user->id === $property->owner_id && $user->tenant_id === $property->tenant_id;
    }
}
