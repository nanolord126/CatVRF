<?php declare(strict_types=1);

namespace App\Http\Middleware;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class RateLimitingMiddleware extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
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
