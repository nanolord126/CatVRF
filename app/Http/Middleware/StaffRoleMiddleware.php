<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Domains\Staff\Domain\Entities\Staff;
use App\Domains\Staff\Domain\Enums\StaffRole;
use App\Domains\Staff\Services\StaffRoleService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * StaffRoleMiddleware — middleware для проверки ролей и разрешений.
 * CatVRF 2026 — PRODUCTION MANDATORY.
 *
 * Проверяет, имеет ли пользователь требуемую роль или разрешение.
 */
final class StaffRoleMiddleware
{
    public function __construct(
        private readonly StaffRoleService $roleService
    ) {}

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string $permission = null, ?string $role = null): Response
    {
        $staff = $this->getCurrentStaff();

        if (!$staff) {
            return response()->json(['error' => 'Unauthenticated'], 401);
        }

        // Если указана роль, проверяем её
        if ($role) {
            $requiredRole = StaffRole::tryFrom($role);
            
            if (!$requiredRole) {
                return response()->json(['error' => 'Invalid role specified'], 400);
            }

            $currentRole = StaffRole::tryFrom($staff->role);
            
            if (!$currentRole || $currentRole->value !== $requiredRole->value) {
                return response()->json([
                    'error' => 'Forbidden',
                    'required_role' => $requiredRole->label(),
                    'current_role' => $currentRole?->label() ?? 'Unknown',
                ], 403);
            }
        }

        // Если указано разрешение, проверяем его
        if ($permission) {
            if (!$this->roleService->hasPermission($staff, $permission)) {
                return response()->json([
                    'error' => 'Forbidden',
                    'required_permission' => $permission,
                ], 403);
            }
        }

        return $next($request);
    }

    /**
     * Возвращает текущего сотрудника.
     */
    private function getCurrentStaff(): ?Staff
    {
        $userId = Auth::id();
        
        if (!$userId) {
            return null;
        }

        return Staff::where('user_id', $userId)->first();
    }
}
