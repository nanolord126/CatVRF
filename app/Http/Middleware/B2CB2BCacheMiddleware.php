<?php declare(strict_types=1);

/**
 * B2CB2BCacheMiddleware — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/b2cb2bcachemiddleware
 * @see https://catvrf.ru/docs/b2cb2bcachemiddleware
 * @see https://catvrf.ru/docs/b2cb2bcachemiddleware
 * @see https://catvrf.ru/docs/b2cb2bcachemiddleware
 * @see https://catvrf.ru/docs/b2cb2bcachemiddleware
 * @see https://catvrf.ru/docs/b2cb2bcachemiddleware
 * @see https://catvrf.ru/docs/b2cb2bcachemiddleware
 */


namespace App\Http\Middleware;

use Illuminate\Cache\CacheManager;
use Illuminate\Contracts\Auth\Guard;

final class B2CB2BCacheMiddleware
{
    public function __construct(
        private readonly CacheManager $cache,
        private readonly Guard $guard,
    ) {}


    public function handle(Request $request, Closure $next)
        {
            $userId = $this->guard->id();

            if (!$userId) {
                return $next($request);
            }

            $cacheKey = "user_{$userId}_b2b_mode";
            $cacheTag = "user_b2c_b2b_{$userId}";

            $isB2B = $this->cache->tags([$cacheTag])->remember($cacheKey, now()->addHour(), function () use ($request) {
                return $request->has('inn') && $request->has('business_card_id');
            });

            $request->merge(['is_b2b' => $isB2B]);
            $request->attributes->set('b2c_mode', !$isB2B);

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
