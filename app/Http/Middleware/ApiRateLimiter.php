<?php
declare(strict_types=1);

namespace App\Http\Middleware;

use App\Services\Security\RateLimiterService;
use Closure;
use Illuminate\Http\Request;

final class ApiRateLimiter
{
    public function __construct(
        private readonly RateLimiterService $rateLimiterService,
    ) {
    }

    /**
     * Handle with tenant-aware sliding window
     * Usage: ->middleware('api-rate-limit:10,60') — 10 requests per 60 seconds
     */
    public function handle(Request $request, Closure $next, string $limit = '100', string $window = '3600'): mixed
    {
        $tenantId = tenant('id') ?? $request->attributes->get('tenant_id') ?? 0;
        $userId = auth()->id() ?? 'anonymous';
        $ip = $request->ip() ?? '0.0.0.0';
        $endpoint = $request->path();

        $limitInt = (int)$limit;
        $windowInt = (int)$window;

        // Check rate limit
        $remaining = $this->checkRateLimit(
            tenantId: $tenantId,
            userId: $userId,
            endpoint: $endpoint,
            limit: $limitInt,
            window: $windowInt
        );

        if ($remaining < 0) {
            return response()->json([
                'error' => 'Rate limit exceeded',
                'retry_after' => $windowInt,
                'correlation_id' => $request->header('X-Correlation-ID'),
            ], 429)->header('Retry-After', $windowInt);
        }

        $response = $next($request);

        return $response
            ->header('X-RateLimit-Limit', $limitInt)
            ->header('X-RateLimit-Remaining', max(0, $remaining))
            ->header('X-RateLimit-Reset', now()->addSeconds($windowInt)->timestamp);
    }

    private function checkRateLimit(
        int $tenantId,
        string $userId,
        string $endpoint,
        int $limit,
        int $window
    ): int {
        $key = "rate_limit:{$tenantId}:{$userId}:{$endpoint}";
        $now = now();
        $windowStart = $now->copy()->subSeconds($window);

        // Get current count from Redis
        $redis = \Illuminate\Support\Facades\Redis::connection('default');
        
        // Remove old entries (outside window)
        $redis->zremrangebyscore($key, '-inf', $windowStart->timestamp);

        // Count requests in current window
        $count = $redis->zcard($key);

        if ($count >= $limit) {
            return -1;
        }

        // Add current request
        $redis->zadd($key, $now->timestamp, uniqid());

        // Set expiry
        $redis->expire($key, $window);

        return $limit - $count - 1;
    }
}
