<?php

declare(strict_types=1);


namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\Response;

final /**
 * CheckTenantPolicy
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class CheckTenantPolicy
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, string $ability = 'view'): Response
    {
        $user = $request->user();

        if (! $user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        // Check tenant access
        if (! Gate::check('view-tenant')) {
            return response()->json(['error' => 'Forbidden - No tenant access'], 403);
        }

        // Verify tenant scoping
        $tenant = filament()->getTenant();
        if ($tenant && $user->tenant_id !== $tenant->id) {
            return response()->json(['error' => 'Forbidden - Tenant mismatch'], 403);
        }

        return $next($request);
    }
}
