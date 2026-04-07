<?php declare(strict_types=1);

namespace App\Domains\Fashion\Policies;

use App\Domains\Fashion\Models\FashionStore;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

/**
 * Class FashionStorePolicy
 *
 * Part of the Fashion vertical domain.
 * Follows CatVRF 9-layer architecture.
 *
 * Authorization policy for resource access control.
 * Enforces tenant-scoped permissions.
 * Integrates with B2C/B2B role system.
 *
 * @package App\Domains\Fashion\Policies
 */
final class FashionStorePolicy
{
    use HandlesAuthorization;

    /**
     * Handle viewAny operation.
     *
     * @throws \DomainException
     */
    public function viewAny(User $user): bool
    {
        return $user->can('view_fashion');
    }

    /**
     * Handle view operation.
     *
     * @throws \DomainException
     */
    public function view(User $user, FashionStore $store): bool
    {
        return $store->tenant_id === $user->tenant_id;
    }

    public function create(User $user): bool
    {
        if (!app(\App\Services\FraudControlService::class)->shouldBlock(0.1, 'create_fashion_store')) {
            return $user->can('manage_fashion');
        }

        return false;
    }

    public function update(User $user, FashionStore $store): bool
    {
        return $store->tenant_id === $user->tenant_id && $user->can('manage_fashion');
    }

    public function delete(User $user, FashionStore $store): bool
    {
        return $store->tenant_id === $user->tenant_id && $user->isAdmin();
    }
}