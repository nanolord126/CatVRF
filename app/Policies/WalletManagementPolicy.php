<?php declare(strict_types=1);

namespace App\Policies;

/**
 * Class WalletManagementPolicy
 *
 * Eloquent model with tenant-scoping and business group isolation.
 * All queries are automatically scoped by tenant_id via global scope.
 *
 * Required fields: uuid, correlation_id, tenant_id, business_group_id, tags (json).
 * Audit logging is handled via model events (created, updated, deleted).
 *
 * @property int $id
 * @property int $tenant_id
 * @property int|null $business_group_id
 * @property string $uuid
 * @property string|null $correlation_id
 * @property array|null $tags
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @package App\Policies
 */
final class WalletManagementPolicy extends Model
{

    use HandlesAuthorization;

        /**
         * Handle view operation.
         *
         * @throws \DomainException
         */
        public function view(User $user, $wallet): bool
        {
            return $user->tenant_id === ($wallet->tenant_id ?? null);
        }

        /**
         * Handle viewBalance operation.
         *
         * @throws \DomainException
         */
        public function viewBalance(User $user, $wallet): bool
        {
            return $user->tenant_id === ($wallet->tenant_id ?? null);
        }

        /**
         * Handle withdraw operation.
         *
         * @throws \DomainException
         */
        public function withdraw(User $user, $wallet): bool
        {
            return $user->tenant_id === ($wallet->tenant_id ?? null)
                && ($user->isBusinessOwner() || $user->hasAbility('finance'));
        }
}
