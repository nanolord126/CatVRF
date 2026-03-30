<?php declare(strict_types=1);

namespace App\Http\Middleware;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class EnsureUserBelongsToTenant extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    /**
         * Handle an incoming request.
         */
        public function handle(Request $request, Closure $next): Response
        {
            $user = $request->user();

            // If not authenticated, let auth middleware handle it
            if (!$user) {
                return $next($request);
            }

            // Platform admins have full access everywhere
            if ($user->isPlatformAdmin()) {
                return $next($request);
            }

            // Detect current tenant from stancl/tenancy global helper
            /** @var Tenant|null */
            $tenant = tenant();

            if ($tenant) {
                // Check if user has ANY business role in this tenant
                if (!$user->hasRoleInTenant($tenant->id, \App\Enums\Role::businessRoles())) {
                    Log::channel('audit')->warning('Tenant access blocked', [
                        'user_id' => $user->id,
                        'tenant_id' => $tenant->id,
                        'ip' => $request->ip(),
                    ]);

                    abort(403, 'У вас нет доступа к этому бизнесу.');
                }
            }

            return $next($request);
        }
}
