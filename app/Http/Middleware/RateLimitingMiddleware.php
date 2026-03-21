<?php
declare(strict_types=1);

namespace App\Http\Middleware;

use App\Services\Security\TenantAwareRateLimiter;
use Closure;
use Illuminate\Http\Request;

final class RateLimitingMiddleware
{
    public function __construct(
        private readonly TenantAwareRateLimiter $rateLimiter,
    ) {
    }

    public function handle(Request $request, Closure $next, ?string $limit = null): mixed
    {
        $tenantId = auth()->user()?->tenant_id ?? 1;
        $key = $request->path();
        
        $limits = [
            'payment' => 30,
            'promo' => 50,
            'wishlist' => 50,
            'search' => 120,
            'webhook' => 1000,
        ];

        $actualLimit = $limits[$limit ?? 'default'] ?? ($limit ? (int)$limit : 100);
        
        if (!$this->rateLimiter->check($tenantId, $key, $actualLimit)) {
            return response()->json([
                'error' => 'Rate limit exceeded',
                'retry_after' => 60,
            ], 429)->header('Retry-After', 60);
        }

        $response = $next($request);
        $remaining = $this->rateLimiter->remaining($tenantId, $key, $actualLimit);

        return $response
            ->header('X-RateLimit-Limit', $actualLimit)
            ->header('X-RateLimit-Remaining', $remaining);
    }
}
