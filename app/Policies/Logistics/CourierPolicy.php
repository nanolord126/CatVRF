<?php declare(strict_types=1);

namespace App\Policies\Logistics;

use App\Domains\Logistics\Models\Courier;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

/**
 * Courier Data Isolation Policy (2026 Edition)
 * 
 * Обеспечивает строгую изоляцию курьеров между тенантами.
 * Канон 2026: FraudControlService::check().
 */
final class CourierPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('view_logistics');
    }

    public function view(User $user, Courier $courier): bool
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
