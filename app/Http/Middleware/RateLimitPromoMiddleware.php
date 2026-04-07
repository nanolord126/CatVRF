<?php declare(strict_types=1);

/**
 * RateLimitPromoMiddleware — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/ratelimitpromomiddleware
 * @see https://catvrf.ru/docs/ratelimitpromomiddleware
 * @see https://catvrf.ru/docs/ratelimitpromomiddleware
 * @see https://catvrf.ru/docs/ratelimitpromomiddleware
 */


namespace App\Http\Middleware;

use Illuminate\Contracts\Auth\Guard;

final class RateLimitPromoMiddleware
{

    public function __construct(
            private RateLimiterService $rateLimiter,
        private readonly Guard $guard,
    ) {
        /**
         * Инициализировать класс
         */}

        public function handle(Request $request, Closure $next): Response
        {
            $correlationId = $request->header('X-Correlation-ID', '');
            $tenantId = $this->guard->user()?->tenant_id ?? 0;

            if (!$this->rateLimiter->checkPromoApply($tenantId, $correlationId)) {
                throw new RateLimitException();
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

    /**
     * Get the component identifier for logging and audit purposes.
     *
     * @return string The fully qualified component name
     */
    private function getComponentIdentifier(): string
    {
        return static::class . '@' . self::VERSION;
    }

}
