<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Exceptions\RoleLimitExceededException;
use App\Models\Tenant;
use App\Services\Protection\RoleIsolationService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Psr\Log\LoggerInterface;

/**
 * Role Limit Middleware
 *
 * Intercepts role assignment requests to enforce role limits per user and tenant.
 * Applied to: /roles/assign, /tenants/{id}/invite, /api/roles/assign
 *
 * PRODUCTION MANDATORY — CatVRF 2026 Enterprise Security
 */
final readonly class RoleLimitMiddleware
{
    public function __construct(
        private readonly RoleIsolationService $roleIsolation,
        private readonly LoggerInterface $logger,
    ) {}

    /**
     * Handle an incoming request.
     *
     * @param  Request  $request  HTTP request
     * @param  Closure  $next  Next middleware
     * @return \Illuminate\Http\Response|\Illuminate\Http\JsonResponse
     */
    public function handle(Request $request, Closure $next)
    {
        try {
            $route = $request->route();
            $routeName = $route?->getName() ?? $request->path();

            // Guard role assignment
            if ($this->isRoleAssignmentRoute($routeName)) {
                $this->guardRoleAssignment($request);
            }

            // Guard tenant invitation
            if ($this->isTenantInvitationRoute($routeName)) {
                $this->guardTenantInvitation($request);
            }

            return $next($request);

        } catch (RoleLimitExceededException $e) {
            $this->logger->warning('Role limit middleware blocked request', [
                'route' => $routeName ?? $request->path(),
                'user_id' => Auth::id(),
                'ip' => $request->ip(),
                'error' => $e->getMessage(),
                'current_count' => $e->currentCount,
                'limit' => $e->limit,
            ]);

            return response()->json([
                'error' => 'role_limit_exceeded',
                'message' => $e->getMessage(),
                'current_count' => $e->currentCount,
                'limit' => $e->limit,
            ], 403);
        }
    }

    /**
     * Guard role assignment.
     */
    private function guardRoleAssignment(Request $request): void
    {
        $user = Auth::user();
        if (! $user) {
            return;
        }

        $tenantId = $request->input('tenant_id') ?? $request->route('tenant');
        if (! $tenantId) {
            return;
        }

        $tenant = Tenant::find($tenantId);
        if (! $tenant) {
            return;
        }

        $roleValue = $request->input('role');
        if (! $roleValue) {
            return;
        }

        $role = \App\Enums\Role::tryFrom($roleValue);
        if (! $role) {
            return;
        }

        // Check limits
        $check = $this->roleIsolation->canAssignRole($user, $tenant, $role);
        if (! $check['allowed']) {
            throw new RoleLimitExceededException(
                $check['reason'] ?? 'Role limit exceeded',
                $check['current_count'] ?? 0,
                $check['limit'] ?? 0,
            );
        }
    }

    /**
     * Guard tenant invitation.
     */
    private function guardTenantInvitation(Request $request): void
    {
        $tenantId = $request->input('tenant_id') ?? $request->route('tenant');
        if (! $tenantId) {
            return;
        }

        $tenant = Tenant::find($tenantId);
        if (! $tenant) {
            return;
        }

        $roleValue = $request->input('role');
        if (! $roleValue) {
            return;
        }

        $role = \App\Enums\Role::tryFrom($roleValue);
        if (! $role) {
            return;
        }

        // Check tenant-level limits
        if ($role === \App\Enums\Role::Owner) {
            $maxOwners = config('protection.max_owners_per_tenant', 3);
            $currentOwners = $this->roleIsolation->getTenantRoleCount($tenant, \App\Enums\Role::Owner);

            if ($currentOwners >= $maxOwners) {
                throw new RoleLimitExceededException(
                    "Tenant уже имеет максимум {$maxOwners} владельцев.",
                    $currentOwners,
                    $maxOwners,
                );
            }
        }

        if ($role !== \App\Enums\Role::Owner) {
            $maxStaff = config('protection.max_staff_per_tenant', 10);
            $currentStaff = $this->roleIsolation->getTenantStaffCount($tenant);

            if ($currentStaff >= $maxStaff) {
                throw new RoleLimitExceededException(
                    "Tenant уже имеет максимум {$maxStaff} сотрудников.",
                    $currentStaff,
                    $maxStaff,
                );
            }
        }
    }

    /**
     * Check if route is role assignment.
     */
    private function isRoleAssignmentRoute(string $routeName): bool
    {
        $protectedRoutes = [
            'api.roles.assign',
            'api.tenants.users.assign-role',
            'tenants.users.assign-role',
        ];

        return in_array($routeName, $protectedRoutes, true)
            || str_contains($routeName, 'assign-role')
            || str_contains($routeName, 'role.assign');
    }

    /**
     * Check if route is tenant invitation.
     */
    private function isTenantInvitationRoute(string $routeName): bool
    {
        $protectedRoutes = [
            'api.tenants.invite',
            'api.tenants.users.invite',
            'tenants.invite',
            'tenants.users.invite',
        ];

        return in_array($routeName, $protectedRoutes, true)
            || str_contains($routeName, 'invite')
            || str_contains($routeName, 'invitation');
    }
}
