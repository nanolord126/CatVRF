<?php declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Cache\RateLimiter;
use Illuminate\Http\Request;

/**
 * Rate Limit Middleware
 * Production 2026 CANON
 *
 * Implements sliding-window rate limiting per endpoint:
 * - 60 req/min for regular endpoints
 * - 30 req/min for create/update/delete
 * - 20 req/min for sensitive operations (payments, refunds)
 * - 10 req/min for high-risk operations (fraud checks)
 * - Tenant-aware limiting (separate buckets per tenant)
 * - IP-based for unauthenticated requests
 *
 * Returns 429 Too Many Requests with Retry-After header
 *
 * @author CatVRF Team
 * @version 2026.03.25
 */
final class RateLimitMiddleware
{
    private RateLimiter $limiter;

    public function __construct(RateLimiter $limiter)
    {
        $this->limiter = $limiter;
    }

    /**
     * Handle the request
     *
     * @param Request $request
     * @param Closure $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        // Determine rate limit based on operation
        $limit = $this->getLimitForOperation($request);

        // Build rate limit key
        $key = $this->buildKey($request);

        // Check if limit exceeded
        if ($this->limiter->tooManyAttempts($key, $limit)) {
            $retryAfter = $this->limiter->availableIn($key);

            return $this->response->json([
                'error' => 'Too many requests',
                'retry_after' => $retryAfter,
                'correlation_id' => $request->get('correlation_id'),
            ], 429, [
                'Retry-After' => $retryAfter,
                'X-RateLimit-Limit' => $limit,
                'X-RateLimit-Remaining' => 0,
            ]);
        }

        // Increment attempt counter
        $this->limiter->hit($key, 60); // 60 second window

        // Continue to next middleware
        $response = $next($request);

        // Add rate limit headers
        $remaining = max(0, $limit - $this->limiter->attempts($key));
        $response->header('X-RateLimit-Limit', $limit);
        $response->header('X-RateLimit-Remaining', $remaining);
        $response->header('X-RateLimit-Reset', now()->addSeconds($this->limiter->availableIn($key))->timestamp);

        return $response;
    }

    /**
     * Get rate limit for operation
     *
     * @param Request $request
     * @return int Requests per minute
     */
    private function getLimitForOperation(Request $request): int
    {
        $path = $request->path();
        $method = $request->method();

        // High-risk operations: 10 req/min
        if (str_contains($path, '/payment/init') || str_contains($path, '/fraud')) {
            return 10;
        }

        // Sensitive mutations: 20 req/min
        if (str_contains($path, '/payment/refund') || str_contains($path, '/wallet')) {
            return 20;
        }

        // Create/update/delete operations: 30 req/min
        if (in_array($method, ['POST', 'PUT', 'PATCH', 'DELETE'])) {
            return 30;
        }

        // Regular GET endpoints: 60 req/min
        return 60;
    }

    /**
     * Build rate limit key
     *
     * @param Request $request
     * @return string Key for rate limiting
     */
    private function buildKey(Request $request): string
    {
        $userId = $request->user()?->id ?? $request->ip();
        $tenantId = $request->get('tenant_id') ?? 'anonymous';
        $endpoint = $request->path();

        // Tenant-aware limiting: separate bucket per tenant
        return "rate-limit:{$tenantId}:user:{$userId}:endpoint:{$endpoint}";
    }
}
