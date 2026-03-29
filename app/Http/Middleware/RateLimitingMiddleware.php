<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Services\Security\TenantAwareRateLimiter;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * RateLimitingMiddleware — Rate limiting на основе tenant + endpoint
 *
 * Production 2026 CANON
 *
 * Ограничивает количество запросов к каждому эндпоинту:
 * - payment: 30 req/min
 * - promo: 50 req/min
 * - wishlist: 50 req/min
 * - search: 120 req/min
 * - webhook: 1000 req/min
 * - default: 100 req/min
 *
 * Tenant-aware: лимиты считаются отдельно для каждого tenant.
 *
 * ✓ Middleware execution order: 5th (correlation-id → auth:sanctum → tenant → b2c-b2b → rate-limit → fraud-check → age-verify)
 *
 * @author CatVRF Team
 * @version 2026.03.28
 */
final class RateLimitingMiddleware
{
    public function __construct(
        private readonly TenantAwareRateLimiter $rateLimiter,
    ) {
    }

    public function handle(Request $request, Closure $next, ?string $limit = null): mixed
    {
        $tenantId = auth()->user()?->tenant_id ?? filament()->getTenant()?->id ?? 1;
        $correlationId = $request->attributes->get('correlation_id') ?? $request->header('X-Correlation-ID');
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
            Log::channel('audit')->warning('Rate limit exceeded', [
                'tenant_id' => $tenantId,
                'user_id' => auth()->id(),
                'endpoint' => $key,
                'limit' => $actualLimit,
                'correlation_id' => $correlationId,
            ]);

            return response()->json([
                'error' => 'Rate limit exceeded',
                'retry_after' => 60,
                'correlation_id' => $correlationId,
            ], 429)->header('Retry-After', 60);
        }

        $response = $next($request);
        $remaining = $this->rateLimiter->remaining($tenantId, $key, $actualLimit);

        return $response
            ->header('X-RateLimit-Limit', $actualLimit)
            ->header('X-RateLimit-Remaining', $remaining)
            ->header('X-RateLimit-Reset', now()->addMinutes(1)->timestamp);
    }
}
