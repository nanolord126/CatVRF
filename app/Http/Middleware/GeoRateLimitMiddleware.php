<?php declare(strict_types=1);

namespace App\Http\Middleware;

use Illuminate\Cache\RateLimiter;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Redis;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

/**
 * Geo Rate Limiting Middleware
 * 
 * Protects geolocation endpoints from abuse:
 * - Geocoding autocomplete
 * - Map search
 * - Route calculations
 */
final readonly class GeoRateLimitMiddleware
{
    private const GEOCODE_LIMIT = 30; // per minute
    private const ROUTE_LIMIT = 20; // per minute
    private const AUTOCOMPLETE_LIMIT = 50; // per minute
    private const TRACKING_LIMIT = 60; // per minute

    public function __construct(
        private readonly RateLimiter $limiter,
    ) {}

    public function handle(Request $request, \Closure $next, string $type = 'default'): SymfonyResponse
    {
        $key = $this->resolveRequestSignature($request);
        $limit = $this->getLimitForType($type);

        if ($this->limiter->tooManyAttempts($key, $limit)) {
            return $this->buildResponse($key, $limit);
        }

        $this->limiter->hit($key, 60); // 1 minute window

        $response = $next($request);

        // Add rate limit headers
        $response->headers->set('X-RateLimit-Limit', (string) $limit);
        $response->headers->set('X-RateLimit-Remaining', (string) $this->limiter->remaining($key, $limit));
        $response->headers->set('X-RateLimit-Reset', (string) $this->limiter->availableIn($key));

        return $response;
    }

    private function resolveRequestSignature(Request $request): string
    {
        $userId = $request->user()?->id ?? $request->ip();
        
        return sha1(
            $userId .
            '|' . $request->route()?->getName() .
            '|' . $request->ip()
        );
    }

    private function getLimitForType(string $type): int
    {
        return match ($type) {
            'geocode' => self::GEOCODE_LIMIT,
            'route' => self::ROUTE_LIMIT,
            'autocomplete' => self::AUTOCOMPLETE_LIMIT,
            'tracking' => self::TRACKING_LIMIT,
            default => 30,
        };
    }

    private function buildResponse(string $key, int $limit): Response
    {
        $seconds = $this->limiter->availableIn($key);

        return new Response(
            [
                'error' => 'Too many requests',
                'message' => 'Rate limit exceeded. Please try again later.',
                'retry_after' => $seconds,
            ],
            429,
            [
                'Retry-After' => $seconds,
                'X-RateLimit-Limit' => (string) $limit,
                'X-RateLimit-Remaining' => '0',
                'X-RateLimit-Reset' => (string) $seconds,
            ]
        );
    }
}
