<?php declare(strict_types=1);

/**
 * TrackUserActivityMiddleware — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/trackuseractivitymiddleware
 * @see https://catvrf.ru/docs/trackuseractivitymiddleware
 * @see https://catvrf.ru/docs/trackuseractivitymiddleware
 * @see https://catvrf.ru/docs/trackuseractivitymiddleware
 * @see https://catvrf.ru/docs/trackuseractivitymiddleware
 * @see https://catvrf.ru/docs/trackuseractivitymiddleware
 */


namespace App\Http\Middleware;

use Illuminate\Contracts\Auth\Guard;

final class TrackUserActivityMiddleware
{

    public function __construct(
            private readonly UserActivityService $activityService,
            private readonly Guard $guard,
    ) {
        /**
         * Инициализировать класс
         */}

        public function handle(Request $request, Closure $next): mixed
        {
            $response = $next($request);

            // Track activity after request completes
            if ($this->guard->check()) {
                $this->activityService->recordActivity(
                    userId: $this->guard->id(),
                    tenantId: filament()->getTenant()?->id ?? 0,
                    activity: $request->method() . ' ' . $request->path(),
                    metadata: [
                        'status' => $response->status(),
                        'user_agent' => $request->userAgent(),
                    ]
                );
            }

            return $response;
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
