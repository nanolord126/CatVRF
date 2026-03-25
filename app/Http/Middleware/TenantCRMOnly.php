<?php declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\Tenant;
use App\Enums\Role;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

final class TenantCRMOnly
{
    /**
     * Only business users (owner/manager/employee/accountant) can access CRM
     * Rejects customers and unauthenticated users
     */
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        // Not authenticated
        if (!$user) {
            return response()->json(['error' => 'Unauthenticated'], 401);
        }

        // Customer users cannot access CRM
        if ($user->role === Role::Customer) {
            $this->log->channel('audit')->warning('Customer attempted CRM access', [
                'user_id' => $user->id,
                'ip' => $request->ip(),
                'path' => $request->path(),
            ]);

            return response()->json(['error' => 'Access denied: CRM not available for customers'], 403);
        }

        // Platform admins always have access
        if ($user->isPlatformAdmin()) {
            return $next($request);
        }

        // Business users must have at least one active tenant assignment
        $hasTenantAccess = $user->tenants()
            ->where('tenant_user.is_active', true)
            ->exists();

        if (!$hasTenantAccess) {
            $this->log->channel('audit')->warning('User without tenant access attempted CRM', [
                'user_id' => $user->id,
                'role' => $user->role->value,
                'ip' => $request->ip(),
            ]);

            return response()->json(['error' => 'No active tenant assignment'], 403);
        }

        return $next($request);
    }
}
