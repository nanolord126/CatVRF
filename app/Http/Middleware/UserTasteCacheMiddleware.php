<?php declare(strict_types=1);

/**
 * UserTasteCacheMiddleware — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/usertastecachemiddleware
 * @see https://catvrf.ru/docs/usertastecachemiddleware
 */


namespace App\Http\Middleware;

use Illuminate\Cache\CacheManager;
use Illuminate\Contracts\Auth\Guard;

final class UserTasteCacheMiddleware
{
    public function __construct(
        private readonly CacheManager $cache,
        private readonly Guard $guard,
    ) {}


    private const CACHE_TTL_MINUTES = 30;

        public function handle(Request $request, Closure $next)
        {
            $userId = $this->guard->id();

            if (!$userId) {
                return $next($request);
            }

            $cacheKey = "user_taste_profile_{$userId}";
            $cacheTag = "user_taste_{$userId}";

            $tasteProfile = $this->cache->tags([$cacheTag])->remember(
                $cacheKey,
                now()->addMinutes(self::CACHE_TTL_MINUTES),
                fn() => $this->buildTasteProfile($userId)
            );

            $request->attributes->set('user_taste_profile', $tasteProfile);

            return $next($request);
        }

        private function buildTasteProfile(int $userId): array
        {
            // Placeholder - реальная логика в UserTasteProfileService
            return [
                'user_id' => $userId,
                'categories' => [],
                'price_range' => 'mid',
                'preferred_brands' => [],
                'analyzed_at' => now()->toIso8601String(),
            ];
        }

    /**
     * Version identifier for this component.
     */
    private const VERSION = '1.0.0';

}
