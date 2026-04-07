<?php declare(strict_types=1);

namespace App\Domains\PromoCampaigns\Policies;

use App\Models\User;
use App\Domains\PromoCampaigns\Models\PromoAuditLog;
use Illuminate\Auth\Access\HandlesAuthorization;

final class PromoAuditLogPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->tenant_id !== null;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, PromoAuditLog $promoAuditLog): bool
    {
        return $user->tenant_id === $promoAuditLog->tenant_id;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->tenant_id !== null;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, PromoAuditLog $promoAuditLog): bool
    {
        return $user->tenant_id === $promoAuditLog->tenant_id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, PromoAuditLog $promoAuditLog): bool
    {
        return $user->tenant_id === $promoAuditLog->tenant_id;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, PromoAuditLog $promoAuditLog): bool
    {
        return $user->tenant_id === $promoAuditLog->tenant_id;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, PromoAuditLog $promoAuditLog): bool
    {
        return false;
    }
}
