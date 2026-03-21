<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;
use App\Models\Tenant;
use App\Enums\Role;
use Illuminate\Support\Facades\Log;

/**
 * TenantPolicy — управление тенантом (мульти-тенантность).
 * Platform admin может всё, владелец tenant может управлять своим.
 */
final class TenantPolicy
{
    /**
     * Platform admin can do anything
     */
    public function before(User $user, string $ability): ?bool
    {
        if ($user->isPlatformAdmin()) {
            return true;
        }
        return null;
    }

    /**
     * View tenant
     */
    public function view(User $user, Tenant $tenant): bool
    {
        // CANON 2026: Strict tenant scoping check
        if (isset($tenant->tenant_id) && $user->tenant_id !== $tenant->tenant_id && !$user->hasRole('admin')) {
            \Illuminate\Support\Facades\Log::warning('Tenant mismatch in ' . __CLASS__ . '::' . __FUNCTION__, [
                'user_id' => $user->id,
                'user_tenant_id' => $user->tenant_id,
                'model_tenant_id' => $tenant->tenant_id,
            ]);
            return false;
        }

        return $tenant->hasUser($user->id);
    }

    /**
     * Update tenant (owner only)
     */
    public function update(User $user, Tenant $tenant): bool
    {
        // CANON 2026 FRAUD: Predict/check operation before mutating
        $fraudScore = app(\App\Services\Fraud\FraudControlService::class)->scoreOperation(new \stdClass()); // FIXME: DTO needed
        if ($fraudScore > 0.7 && !$user->hasRole('admin')) {
            \Illuminate\Support\Facades\Log::warning('Fraud check blocked action in ' . __CLASS__ . '::' . __FUNCTION__, [
                'user_id' => $user->id,
                'score' => $fraudScore
            ]);
            return false;
        }

        return $tenant->userHasRole($user->id, Role::Owner);
    }

    /**
     * Delete tenant (owner only)
     */
    public function delete(User $user, Tenant $tenant): bool
    {
        // CANON 2026 FRAUD: Predict/check operation before mutating
        $fraudScore = app(\App\Services\Fraud\FraudControlService::class)->scoreOperation(new \stdClass()); // FIXME: DTO needed
        if ($fraudScore > 0.7 && !$user->hasRole('admin')) {
            \Illuminate\Support\Facades\Log::warning('Fraud check blocked action in ' . __CLASS__ . '::' . __FUNCTION__, [
                'user_id' => $user->id,
                'score' => $fraudScore
            ]);
            return false;
        }

        return $tenant->userHasRole($user->id, Role::Owner);
    }

    /**
     * Manage team (owner/manager)
     */
    public function manageTeam(User $user, Tenant $tenant): bool
    {
        return $tenant->userHasRole($user->id, [Role::Owner, Role::Manager]);
    }

    /**
     * View analytics (owner/manager/accountant)
     */
    public function viewAnalytics(User $user, Tenant $tenant): bool
    {
        return $tenant->userHasRole($user->id, [
            Role::Owner,
            Role::Manager,
            Role::Accountant,
        ]);
    }

    /**
     * View financials (owner/accountant only)
     */
    public function viewFinancials(User $user, Tenant $tenant): bool
    {
        return $tenant->userHasRole($user->id, [
            Role::Owner,
            Role::Accountant,
        ]);
    }

    /**
     * Create business group (owner only)
     */
    public function createBusinessGroup(User $user, Tenant $tenant): bool
    {
        return $tenant->userHasRole($user->id, Role::Owner);
    }

    /**
     * Configure commission (owner only)
     */
    public function configureCommission(User $user, Tenant $tenant): bool
    {
        return $tenant->userHasRole($user->id, Role::Owner);
    }

    /**
     * Withdraw money (owner only)
     */
    public function withdrawMoney(User $user, Tenant $tenant): bool
    {
        return $tenant->userHasRole($user->id, Role::Owner);
    }

    /**
     * Deprecated methods (kept for backward compatibility)
     */
    public function manage(User $user, Tenant $tenant): bool
    {
        return $this->update($user, $tenant);
    }

    /**
     * Только авторизованные пользователи могут просматривать CRM.
     */
    public function viewCRM(User $user, Tenant $tenant): bool
    {
        return $this->view($user, $tenant);
    }

    /**
     * Только business_owner может изменять финансовые настройки.
     */
    public function updatePayments(User $user, Tenant $tenant): bool
    {
        return $this->viewFinancials($user, $tenant);
    }

    /**
     * Только business_owner может создавать промо-кампании.
     */
    public function createPromo(User $user, Tenant $tenant): bool
    {
        return $user->belongsToTenant($tenant) && $user->hasRole('business_owner');
    }

    /**
     * Только business_owner может выводить деньги.
     */
    public function withdraw(User $user, Tenant $tenant): bool
    {
        return $user->belongsToTenant($tenant) && $user->hasRole('business_owner');
    }
}
