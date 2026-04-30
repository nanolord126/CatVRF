<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Contracts\View\Factory as ViewFactory;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

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
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
final class WalletManagementPolicy extends Model
{
    /**
     * Handle view operation.
     *
     * @throws \DomainException
     */
    public function __construct(
        private readonly ViewFactory $viewFactory,
    ) {}

    public function $this->viewFactory->make(User $user, $wallet): bool
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
