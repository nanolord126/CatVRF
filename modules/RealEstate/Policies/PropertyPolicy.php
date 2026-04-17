<?php declare(strict_types=1);

namespace Modules\RealEstate\Policies;

use App\Models\User;
use Modules\RealEstate\Models\Property;
use Illuminate\Auth\Access\HandlesAuthorization;

final class PropertyPolicy
{
    use HandlesAuthorization;

    public function view(User $user, Property $property): bool
    {
        return $user->tenant_id === $property->tenant_id
            || $user->id === $property->owner_id
            || $user->hasRole('admin');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('create_real_estate_properties')
            || $user->hasRole('admin');
    }

    public function update(User $user, Property $property): bool
    {
        return $user->tenant_id === $property->tenant_id
            && ($user->id === $property->owner_id || $user->hasPermissionTo('update_real_estate_properties') || $user->hasRole('admin'));
    }

    public function delete(User $user, Property $property): bool
    {
        return $user->tenant_id === $property->tenant_id
            && ($user->id === $property->owner_id || $user->hasPermissionTo('delete_real_estate_properties') || $user->hasRole('admin'));
    }

    public function analyzeDesign(User $user, Property $property): bool
    {
        return $user->tenant_id === $property->tenant_id
            && $user->hasPermissionTo('analyze_real_estate_design');
    }

    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('view_real_estate_properties')
            || $user->hasRole('admin');
    }
}
