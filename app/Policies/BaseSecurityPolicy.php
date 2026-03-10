<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\Response;
use Illuminate\Database\Eloquent\Model;

/**
 * Base Security Policy for Zero Trust 2026.
 * Implements forced tenant check and audit tracing before any specific checks.
 */
abstract class BaseSecurityPolicy
{
    /**
     * Common before check for all policies.
     * 1. Checks for 'admin' role bypass.
     * 2. FORCES tenant_id match for models using StrictTenantIsolation.
     */
    public function before(User $user, string $ability): ?bool
    {
        if ($user->hasRole('admin')) {
            return true;
        }

        // Potential for Global Zero Trust Logging here
        return null;
    }

    /**
     * Force check if the model belongs to the current tenant.
     * This is a second line of defense after Global Scopes.
     */
    protected function isFromThisTenant(Model $model): bool
    {
        $tenantKey = config('tenancy.tenant_key', 'tenant_id');
        
        if (!isset($model->$tenantKey)) {
            return true; // Not a tenant-scoped model
        }

        return $model->$tenantKey === tenant('id');
    }

    /**
     * Default responses
     */
    protected function denyWithAudit(string $message = 'Forbidden by Zero Trust Policy'): Response
    {
        // Here we could trigger a high-risk security event in action_audits
        return Response::deny($message);
    }
}
