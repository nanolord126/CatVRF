<?php declare(strict_types=1);

namespace App\Http\Middleware;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class CheckTenantPolicy extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
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
