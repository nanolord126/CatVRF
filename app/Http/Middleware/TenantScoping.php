<?php declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\Tenant;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

final class TenantScoping
{
    /**
     * Set active tenant in request + session
     * Automatically filters queries by this tenant
     */
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        if (!$user) {
            return $next($request);
        }

        // Platform admins can specify tenant via header or session
        if ($user->isPlatformAdmin()) {
            $tenantId = $request->header('X-Tenant-ID')
                ?? session('active_tenant_id');

            if ($tenantId) {
                $tenant = Tenant::find($tenantId);
                if ($tenant) {
                    $request->attributes->set('tenant', $tenant);
                    session(['active_tenant_id' => $tenantId]);
                }
            }

            return $next($request);
        }

        // Business users: get tenant from route, header, or session
        $tenantId = $request->route('tenant_id')
            ?? $request->header('X-Tenant-ID')
            ?? session('active_tenant_id');

        // If no tenant specified, use user's first active tenant
        if (!$tenantId) {
            $tenant = $user->tenants()
                ->where('tenant_user.is_active', true)
                ->first();

            if ($tenant) {
                $tenantId = $tenant->id;
            }
        }

        // Verify user has access to this tenant
        if ($tenantId && !$user->hasRoleInTenant($tenantId, null)) {
            $this->log->channel('audit')->warning('TenantScoping: access denied', [
                'user_id' => $user->id,
                'tenant_id' => $tenantId,
                'ip' => $request->ip(),
            ]);

            return response()->json(['error' => 'Access denied to this tenant'], 403);
        }

        // Set active tenant
        if ($tenantId) {
            $tenant = Tenant::find($tenantId);
            if ($tenant) {
                $request->attributes->set('tenant', $tenant);
                session(['active_tenant_id' => $tenantId]);

                // Log tenant access
                $this->log->channel('audit')->info('TenantScoping: tenant set', [
                    'user_id' => $user->id,
                    'tenant_id' => $tenantId,
                    'tenant_name' => $tenant->name,
                ]);
            }
        }

        return $next($request);
    }
}
