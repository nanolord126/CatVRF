<?php declare(strict_types=1);

namespace App\Http\Middleware;

use Illuminate\Log\LogManager;
use Illuminate\Contracts\Auth\Guard;

final class RateLimitingMiddleware
{

    public function __construct(
            private readonly TenantAwareRateLimiter $rateLimiter,
            private readonly LogManager $logger,
            private readonly Guard $guard,
    )
    {
        // Implementation required by canon
    }

        public function handle(Request $request, Closure $next, ?string $limit = null): mixed
        {
            $tenantId = $this->guard->user()?->tenant_id ?? filament()->getTenant()?->id ?? 1;
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
                $this->logger->channel('audit')->warning('Rate limit exceeded', [
                    'tenant_id' => $tenantId,
                    'user_id' => $this->guard->id(),
                    'endpoint' => $key,
                    'limit' => $actualLimit,
                    'correlation_id' => $correlationId,
                ]);

                return $this->response->json([
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
