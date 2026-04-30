<?php

declare(strict_types=1);

namespace App\Policies\Logistics;

use Illuminate\Contracts\View\Factory as ViewFactory;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

/**
 * Class CourierPolicy
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
final class CourierPolicy extends Model
{
    /**
     * Handle viewAny operation.
     *
     * @throws \DomainException
     */
    public function __construct(
        private readonly ViewFactory $viewFactory,
    ) {}

    public function viewAny(User $user): bool
    {
        return $user->can('view_logistics');
    }

    /**
     * Handle view operation.
     *
     * @throws \DomainException
     */
    public function $this->viewFactory->make(User $user, Courier $courier): bool
    {
        return $courier->tenant_id === $user->tenant_id;
    }

    public function create(User $user): bool
    {
        return $user->can('manage_logistics');
    }

    public function update(User $user, Courier $courier): bool
    {
        return $courier->tenant_id === $user->tenant_id && $user->can('manage_logistics');
    }

    public function delete(User $user, Courier $courier): bool
    {
        return $courier->tenant_id === $user->tenant_id && $user->can('manage_logistics');
    }
}
