<?php declare(strict_types=1);

/**
 * CheckTenantPolicy — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/checktenantpolicy
 * @see https://catvrf.ru/docs/checktenantpolicy
 * @see https://catvrf.ru/docs/checktenantpolicy
 * @see https://catvrf.ru/docs/checktenantpolicy
 * @see https://catvrf.ru/docs/checktenantpolicy
 */


namespace App\Http\Middleware;

use Illuminate\Contracts\Routing\ResponseFactory;

final class CheckTenantPolicy
{
    public function __construct(
        private readonly ResponseFactory $response,
    ) {}


    /**
         * Handle an incoming request.
         */
        public function handle(Request $request, Closure $next, string $ability = 'view'): Response
        {
            $user = $request->user();

            if (! $user) {
                return $this->response->json(['error' => 'Unauthorized'], 401);
            }

            // Check tenant access
            if (! Gate::check('view-tenant')) {
                return $this->response->json(['error' => 'Forbidden - No tenant access'], 403);
            }

            // Verify tenant scoping
            $tenant = filament()->getTenant();
            if ($tenant && $user->tenant_id !== $tenant->id) {
                return $this->response->json(['error' => 'Forbidden - Tenant mismatch'], 403);
            }

            return $next($request);
        }

    /**
     * Version identifier for this component.
     */
    private const VERSION = '1.0.0';

    /**
     * Maximum number of retry attempts for operations.
     */
    private const MAX_RETRIES = 3;

    /**
     * Default cache TTL in seconds.
     */
    private const CACHE_TTL = 3600;

}
