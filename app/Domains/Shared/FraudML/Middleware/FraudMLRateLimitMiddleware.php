<?php

declare(strict_types=1);

namespace App\Domains\FraudML\Middleware;

use Illuminate\Http\JsonResponse;

use Closure;
use Illuminate\Cache\RateLimiter;
use Illuminate\Http\Request;
use Illuminate\Log\LogManager;
use Symfony\Component\HttpFoundation\Response;

/**
 * FraudMLRateLimitMiddleware - Rate limiting for payment fraud ML endpoints
 *
 * CRITICAL: Prevents abuse of fraud ML service
 * - 30 requests per minute per user
 * - 100 requests per minute per tenant
 * - 1000 requests per minute global
 *
 * CANON 2026 - Production Ready
 */
final class FraudMLRateLimitMiddleware
{
    private const USER_LIMIT = 30;

    private const TENANT_LIMIT = 100;

    private const GLOBAL_LIMIT = 1000;

    private const DECAY_SECONDS = 60;

    public function __construct(
        private readonly RateLimiter $limiter,
        private readonly LogManager $log,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $userId = $request->input('user_id') ?? $request->user()?->id ?? 'anonymous';
        $tenantId = $request->input('tenant_id') ?? $request->header('X-Tenant-ID') ?? 'default';
        $ipAddress = $request->ip();

        // Check user-level rate limit
        $userKey = "fraud_ml:user:{$userId}";
        if ($this->limiter->tooManyAttempts($userKey, self::USER_LIMIT)) {
            $this->log->warning('FraudML rate limit exceeded - user level', [
                'user_id' => $userId,
                'ip_address' => $ipAddress,
                'limit' => self::USER_LIMIT,
            ]);

            return new JsonResponse([
                'error' => 'Rate limit exceeded',
                'message' => 'Too many fraud check requests. Please try again later.',
                'retry_after' => $this->limiter->availableIn($userKey),
            ], 429);
        }

        // Check tenant-level rate limit
        $tenantKey = "fraud_ml:tenant:{$tenantId}";
        if ($this->limiter->tooManyAttempts($tenantKey, self::TENANT_LIMIT)) {
            $this->log->warning('FraudML rate limit exceeded - tenant level', [
                'tenant_id' => $tenantId,
                'ip_address' => $ipAddress,
                'limit' => self::TENANT_LIMIT,
            ]);

            return new JsonResponse([
                'error' => 'Rate limit exceeded',
                'message' => 'Tenant rate limit exceeded. Please contact support.',
                'retry_after' => $this->limiter->availableIn($tenantKey),
            ], 429);
        }

        // Check global rate limit
        $globalKey = 'fraud_ml:global';
        if ($this->limiter->tooManyAttempts($globalKey, self::GLOBAL_LIMIT)) {
            $this->log->warning('FraudML rate limit exceeded - global level', [
                'ip_address' => $ipAddress,
                'limit' => self::GLOBAL_LIMIT,
            ]);

            return new JsonResponse([
                'error' => 'Service temporarily overloaded',
                'message' => 'Too many requests. Please try again later.',
                'retry_after' => $this->limiter->availableIn($globalKey),
            ], 429);
        }

        // Increment counters
        $this->limiter->hit($userKey, self::DECAY_SECONDS);
        $this->limiter->hit($tenantKey, self::DECAY_SECONDS);
        $this->limiter->hit($globalKey, self::DECAY_SECONDS);

        // Add rate limit headers
        $response = $next($request);
        $response->headers->set('X-RateLimit-Limit-User', (string) self::USER_LIMIT);
        $response->headers->set('X-RateLimit-Limit-Tenant', (string) self::TENANT_LIMIT);
        $response->headers->set('X-RateLimit-Limit-Global', (string) self::GLOBAL_LIMIT);
        $response->headers->set('X-RateLimit-Remaining-User', (string) $this->limiter->remaining($userKey, self::USER_LIMIT));
        $response->headers->set('X-RateLimit-Remaining-Tenant', (string) $this->limiter->remaining($tenantKey, self::TENANT_LIMIT));

        return $response;
    }
}
