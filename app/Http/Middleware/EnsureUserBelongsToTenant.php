declare(strict_types=1);

<?php declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\Tenant;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Log;

final /**
 * EnsureUserBelongsToTenant
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class EnsureUserBelongsToTenant
{
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
                $this->log->channel('audit')->warning('Tenant access blocked', [
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
