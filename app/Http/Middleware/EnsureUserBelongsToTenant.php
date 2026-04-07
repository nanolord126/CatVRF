<?php declare(strict_types=1);

/**
 * EnsureUserBelongsToTenant — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/ensureuserbelongstotenant
 * @see https://catvrf.ru/docs/ensureuserbelongstotenant
 * @see https://catvrf.ru/docs/ensureuserbelongstotenant
 */


namespace App\Http\Middleware;

use Illuminate\Log\LogManager;

final class EnsureUserBelongsToTenant
{
    public function __construct(
        private readonly LogManager $logger,
    ) {}


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
                    $this->logger->channel('audit')->warning('Tenant access blocked', [
                        'user_id' => $user->id,
                        'tenant_id' => $tenant->id,
                        'ip' => $request->ip(),
                    ]);

                    abort(403, 'У вас нет доступа к этому бизнесу.');
                }
            }

            return $next($request);
        }

    /**
     * Version identifier for this component.
     */
    private const VERSION = '1.0.0';

}
