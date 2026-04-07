<?php declare(strict_types=1);

namespace App\Http\Middleware;

use Illuminate\Cache\CacheManager;
use Illuminate\Contracts\Auth\Guard;

final class ResponseCacheMiddleware
{
    public function __construct(
        private readonly CacheManager $cache,
        private readonly Guard $guard,
    ) {}


    private const CACHEABLE_METHODS = ['GET', 'HEAD'];
        private const CACHE_TTL_MINUTES = 10;

        public function handle(Request $request, Closure $next)
        {
            if (!$this->isCacheable($request)) {
                return $next($request);
            }

            $cacheKey = $this->generateCacheKey($request);

            if ($this->cache->has($cacheKey)) {
                return $this->cache->get($cacheKey);
            }

            $response = $next($request);

            if ($response->isSuccessful()) {
                $userId = $this->guard->id() ?? 'guest';
                $this->cache->tags(["response_{$userId}"])->put(
                    $cacheKey,
                    $response,
                    now()->addMinutes(self::CACHE_TTL_MINUTES)
                );
            }

            return $response;
        }

        private function isCacheable(Request $request): bool
        {
            return in_array($request->getMethod(), self::CACHEABLE_METHODS, true)
                && !$request->has('no-cache')
                && $this->guard->check();
        }

        private function generateCacheKey(Request $request): string
        {
            $userId = $this->guard->id() ?? 'guest';
            $urlHash = md5($request->fullUrl());

            return "response_{$userId}_{$urlHash}";
        }
}
