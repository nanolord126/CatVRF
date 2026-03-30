<?php declare(strict_types=1);

namespace App\Http\Middleware;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class ResponseCacheMiddleware extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    private const CACHEABLE_METHODS = ['GET', 'HEAD'];
        private const CACHE_TTL_MINUTES = 10;

        public function handle(Request $request, Closure $next)
        {
            if (!$this->isCacheable($request)) {
                return $next($request);
            }

            $cacheKey = $this->generateCacheKey($request);

            if (Cache::has($cacheKey)) {
                return Cache::get($cacheKey);
            }

            $response = $next($request);

            if ($response->isSuccessful()) {
                $userId = auth()->id() ?? 'guest';
                Cache::tags(["response_{$userId}"])->put(
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
                && auth()->check();
        }

        private function generateCacheKey(Request $request): string
        {
            $userId = auth()->id() ?? 'guest';
            $urlHash = md5($request->fullUrl());

            return "response_{$userId}_{$urlHash}";
        }
}
