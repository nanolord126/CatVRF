<?php declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Auth\AuthenticationException;

/**
 * Tenant Middleware
 * Production 2026 CANON
 *
 * Validates and scopes tenant from request:
 * - Extracts tenant_id from Sanctum token
 * - Validates tenant exists and is active
 * - Stores tenant_id in request context
 * - Prevents cross-tenant data access
 * - Applies tenant scoping to all queries (if using query scopes)
 *
 * @author CatVRF Team
 * @version 2026.03.25
 */
final class TenantMiddleware
{
    /**
     * Handle the request
     *
     * @param Request $request
     * @param Closure $next
     * @return mixed
     * @throws AuthenticationException
     */
    public function handle(Request $request, Closure $next)
    {
        // Get tenant from authenticated user
        $user = $request->user();

        if (!$user) {
            throw new AuthenticationException('User not authenticated');
        }

        // Get tenant_id from user
        $tenantId = $user->tenant_id ?? null;

        if (!$tenantId) {
            throw new AuthenticationException('User has no tenant assigned');
        }

        // Verify tenant exists and is active
        $tenant = \$this->db->table('tenants')
            ->where('id', $tenantId)
            ->where('is_active', true)
            ->first();

        if (!$tenant) {
            throw new AuthenticationException('Tenant not found or inactive');
        }

        // Store tenant context in request
        $request->attributes->set('tenant_id', $tenantId);
        $request->attributes->set('tenant', $tenant);

        // Store in auth guard for access throughout request
        app('tenant.context')->setTenant($tenantId);

        return $next($request);
    }
}
