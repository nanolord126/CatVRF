<?php
namespace App\Policies;
use App\Models\User;
use App\Domains\Geo\Models\GeoZone;
use Illuminate\Auth\Access\HandlesAuthorization;

class GeoZonePolicy extends BaseSecurityPolicy {
    use HandlesAuthorization;

    public function viewAny(User $user): bool {
        return $user->hasAnyRole(['admin', 'tenant-owner', 'manager']);
    }

    public function view(User $user, GeoZone $zone): bool {
        return $user->tenant_id === $zone->tenant_id && $user->hasAnyRole(['admin', 'tenant-owner', 'manager']);
    }

    public function create(User $user): bool {
        return $user->tenant_id !== null && $user->hasAnyRole(['admin', 'tenant-owner', 'manager']);
    }

    public function update(User $user, GeoZone $zone): bool {
        return $user->tenant_id === $zone->tenant_id && $user->hasAnyRole(['admin', 'tenant-owner', 'manager']);
    }

    public function delete(User $user, GeoZone $zone): bool {
        return $user->hasRole('admin') && $user->tenant_id === $zone->tenant_id;
    }
}
