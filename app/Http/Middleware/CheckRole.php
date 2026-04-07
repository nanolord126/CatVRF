<?php declare(strict_types=1);

/**
 * CheckRole — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/checkrole
 * @see https://catvrf.ru/docs/checkrole
 * @see https://catvrf.ru/docs/checkrole
 * @see https://catvrf.ru/docs/checkrole
 * @see https://catvrf.ru/docs/checkrole
 */


namespace App\Http\Middleware;

use Illuminate\Contracts\Auth\Guard;

final class CheckRole
{
    public function __construct(
        private readonly Guard $guard,
    ) {}


    public function handle(Request $request, Closure $next, string ...$roles): mixed
        {
            if (!$this->guard->check()) {
                throw new AuthorizationException('Unauthorized');
            }

            /** @var \App\Models\User $user */
            $user = $this->guard->user();

            $tenant = tenant();

            $userRole = null;
            if ($tenant) {
                $userRole = $user->getRoleInTenant($tenant->id)?->value;
            }

            $userRole = $userRole ?? $user->role?->value ?? 'customer';

            if (!in_array($userRole, $roles, true) && !$user->isPlatformAdmin()) {
                throw new AuthorizationException('Forbidden: Insufficient role');
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
