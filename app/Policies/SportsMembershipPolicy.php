<?php
namespace App\Policies;
use App\Models\User;
use App\Domains\Sports\Models\SportsMembership;
use Illuminate\Auth\Access\HandlesAuthorization;

class SportsMembershipPolicy extends BaseSecurityPolicy {
    use HandlesAuthorization;

    public function viewAny(User $user): bool {
        return $user->hasAnyRole(['admin', 'tenant-owner', 'manager', 'sports-staff']);
    }

    public function view(User $user, SportsMembership $membership): bool {
        if ($user->tenant_id !== $membership->tenant_id) return false;
        if ($user->hasAnyRole(['admin', 'tenant-owner', 'manager', 'sports-staff'])) return true;
        return $membership->user_id === $user->id;
    }

    public function create(User $user): bool {
        return $user->tenant_id !== null;
    }

    public function update(User $user, SportsMembership $membership): bool {
        if ($user->tenant_id !== $membership->tenant_id) return false;
        if ($user->hasAnyRole(['admin', 'tenant-owner', 'manager', 'sports-staff'])) return true;
        return $membership->user_id === $user->id && $membership->status === 'active';
    }

    public function cancel(User $user, SportsMembership $membership): bool {
        if ($user->tenant_id !== $membership->tenant_id) return false;
        return $user->hasAnyRole(['admin', 'tenant-owner', 'manager', 'sports-staff']) || 
               ($membership->user_id === $user->id && $membership->status === 'active');
    }

    public function renew(User $user, SportsMembership $membership): bool {
        if ($user->tenant_id !== $membership->tenant_id) return false;
        return $user->hasAnyRole(['admin', 'tenant-owner', 'manager', 'sports-staff']) ||
               $membership->user_id === $user->id;
    }

    public function delete(User $user, SportsMembership $membership): bool {
        return $user->hasRole('admin') && $user->tenant_id === $membership->tenant_id;
    }
}
