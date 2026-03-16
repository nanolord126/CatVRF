<?php

declare(strict_types=1);

namespace App\Domains\Common\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

final class CommonResourcePolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->tenant_id !== null;
    }

    public function view(User $user, $resource): bool
    {
        if (!method_exists($resource, 'getTenantKey')) {
            return true;
        }

        $tenantKey = $resource->getTenantKey();

        if ($user->hasAnyRole(['admin', 'tenant-owner', 'manager'])) {
            return $user->tenant_id === $resource->$tenantKey;
        }

        return $resource->user_id === $user->id &&
               $user->tenant_id === $resource->$tenantKey;
    }

    public function create(User $user): bool
    {
        return $user->tenant_id !== null;
    }

    public function update(User $user, $resource): bool
    {
        if (!method_exists($resource, 'getTenantKey')) {
            return $user->hasAnyRole(['admin', 'tenant-owner']);
        }

        $tenantKey = $resource->getTenantKey();

        if ($user->tenant_id !== $resource->$tenantKey) {
            return false;
        }

        if ($user->hasAnyRole(['admin', 'tenant-owner', 'manager'])) {
            return true;
        }

        return $resource->user_id === $user->id;
    }

    public function delete(User $user, $resource): bool
    {
        if (!method_exists($resource, 'getTenantKey')) {
            return $user->hasRole('admin');
        }

        $tenantKey = $resource->getTenantKey();

        if ($user->tenant_id !== $resource->$tenantKey) {
            return false;
        }

        if ($user->hasRole('admin')) {
            return true;
        }

        return $user->hasAnyRole(['tenant-owner', 'manager']) &&
               $resource->user_id === $user->id;
    }

    public function restore(User $user, $resource): bool
    {
        if (!method_exists($resource, 'getTenantKey')) {
            return $user->hasRole('admin');
        }

        $tenantKey = $resource->getTenantKey();

        return $user->tenant_id === $resource->$tenantKey &&
               $user->hasAnyRole(['admin', 'tenant-owner', 'manager']);
    }

    public function forceDelete(User $user, $resource): bool
    {
        return $user->hasRole('admin');
    }
}
