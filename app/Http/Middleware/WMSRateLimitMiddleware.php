<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Cache\RateLimiter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

/**
 * WMS Rate Limit Middleware
 *
 * Implements rate limiting for WMS API operations:
 * - Per-tenant rate limits
 * - Per-endpoint rate limits
 * - Configurable limits for different operations
 *
 * @author CatVRF Team
 * @version 2026.04.28
 */
final readonly class WMSRateLimitMiddleware
{
    private const DEFAULT_LIMIT = 60;
    private const DEFAULT_WINDOW = 60;

    public function __construct(
        private readonly RateLimiter $limiter,
    ) {}

    public function handle(Request $request, Closure $next): HttpResponse
    {
        $key = $this->resolveRequestSignature($request);

        $limit = $this->resolveLimit($request);
        $window = $this->resolveWindow($request);

        if ($this->limiter->tooManyAttempts($key, $limit, $window)) {
            return $this->buildResponse($key, $limit);
        }

        $this->limiter->hit($key, $window);

        $response = $next($request);

        $response->headers->set('X-RateLimit-Limit', (string) $limit);
        $response->headers->set('X-RateLimit-Remaining', (string) $this->limiter->remaining($key, $limit));

        return $response;
    }

    private function resolveRequestSignature(Request $request): string
    {
        $tenantId = $request->user()?->tenant_id ?? 'anonymous';
        $routeName = $request->route()?->getName() ?? $request->path();

        return sha1($tenantId.'|'.$routeName.'|'.$request->ip());
    }

    private function resolveLimit(Request $request): int
    {
        $routeName = $request->route()?->getName();

        return match (true) {
            str_starts_with($routeName ?? '', 'inventory.update') => 30,
            str_starts_with($routeName ?? '', 'inventory.delete') => 10,
            str_starts_with($routeName ?? '', 'warehouse.manage') => 20,
            default => self::DEFAULT_LIMIT,
        };
    }

    private function resolveWindow(Request $request): int
    {
        return self::DEFAULT_WINDOW;
    }

    private function buildResponse(string $key, int $limit): HttpResponse
    {
        $seconds = $this->limiter->availableIn($key);

        return Response::json([
            'error' => 'Too many requests',
            'message' => 'Rate limit exceeded. Please try again later.',
            'retry_after' => $seconds,
        ], 429)->header('Retry-After', (string) $seconds);
    }
}
