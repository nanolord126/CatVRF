<?php declare(strict_types=1);

namespace App\Http\Middleware;

use App\Enums\Role;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

final class RoleBasedAccess
{
    /**
     * Check if user has required role in current tenant
     * Usage: middleware('role:owner,manager')
     * Usage: middleware('role:admin')
     */
    public function handle(Request $request, Closure $next, ...$roles)
    {
        $user = $request->user();

        // Not authenticated
        if (!$user) {
            return response()->json(['error' => 'Unauthenticated'], 401);
        }

        // Platform admins always have access
        if ($user->isPlatformAdmin()) {
            return $next($request);
        }

        // Get current tenant from request
        $tenantId = $request->route('tenant_id')
            ?? $request->header('X-Tenant-ID')
            ?? session('active_tenant_id');

        if (!$tenantId) {
            $this->log->channel('audit')->warning('RoleBasedAccess: no tenant context', [
                'user_id' => $user->id,
                'path' => $request->path(),
            ]);

            return response()->json(['error' => 'Tenant context required'], 400);
        }

        // Convert string roles to Role enums
        $requiredRoles = array_map(function ($role) {
            try {
                return Role::from($role);
            } catch (\Throwable) {
                return null;
            }
        }, $roles);

        $requiredRoles = array_filter($requiredRoles);

        if (empty($requiredRoles)) {
            $this->log->channel('audit')->warning('RoleBasedAccess: invalid roles', [
                'user_id' => $user->id,
                'provided_roles' => $roles,
            ]);

            return response()->json(['error' => 'Invalid role specification'], 400);
        }

        // Check if user has one of required roles in tenant
        if (!$user->hasRoleInTenant($tenantId, $requiredRoles)) {
            $this->log->channel('audit')->warning('RoleBasedAccess: denied', [
                'user_id' => $user->id,
                'tenant_id' => $tenantId,
                'required_roles' => array_map(fn($r) => $r->value, $requiredRoles),
                'path' => $request->path(),
            ]);

            return response()->json([
                'error' => 'Insufficient permissions for this tenant',
                'required_roles' => array_map(fn($r) => $r->label(), $requiredRoles),
            ], 403);
        }

        return $next($request);
    }
}
