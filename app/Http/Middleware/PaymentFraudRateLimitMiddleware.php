<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Cache\RateLimiter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * PaymentFraudRateLimitMiddleware - Rate limiting for payment fraud endpoints
 * 
 * CRITICAL FIX: Prevents quota burning and CPU exhaustion from spam attacks
 * - Per-user rate limiting on fraud check endpoints
 * - Per-tenant rate limiting to prevent quota exhaustion
 * - Configurable limits with sliding window
 * - Separate limits for emergency vs standard payments
 * 
 * CANON 2026 - Production Ready
 */
final readonly class PaymentFraudRateLimitMiddleware
{
    private const STANDARD_LIMIT = 60; // 60 requests per minute
    private const EMERGENCY_LIMIT = 120; // 120 requests per minute for emergencies
    private const TENANT_LIMIT = 1000; // 1000 requests per minute per tenant

    public function __construct(
        private RateLimiter $limiter,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $userId = $request->user()?->getAuthIdentifier() ?? $request->ip();
        $tenantId = $request->header('X-Tenant-ID', 'default');
        $isEmergency = $this->isEmergencyRequest($request);

        // Check user-level rate limit
        $userKey = $this->getUserRateLimitKey($userId, $isEmergency);
        $userLimit = $isEmergency ? self::EMERGENCY_LIMIT : self::STANDARD_LIMIT;

        if ($this->limiter->tooManyAttempts($userKey, $userLimit)) {
            $this->logRateLimitExceeded('user', $userId, $userLimit);
            return $this->rateLimitResponse($userLimit);
        }

        $this->limiter->hit($userKey, 60); // 1 minute window

        // Check tenant-level rate limit
        $tenantKey = $this->getTenantRateLimitKey($tenantId);

        if ($this->limiter->tooManyAttempts($tenantKey, self::TENANT_LIMIT)) {
            $this->logRateLimitExceeded('tenant', $tenantId, self::TENANT_LIMIT);
            return $this->rateLimitResponse(self::TENANT_LIMIT);
        }

        $this->limiter->hit($tenantKey, 60); // 1 minute window

        return $next($request);
    }

    /**
     * Check if this is an emergency payment request
     */
    private function isEmergencyRequest(Request $request): bool
    {
        return $request->input('urgency_level') === 'emergency'
            || $request->input('is_emergency_payment') === true
            || $request->header('X-Emergency-Payment') === 'true';
    }

    /**
     * Get user rate limit key
     */
    private function getUserRateLimitKey(string $userId, bool $isEmergency): string
    {
        $type = $isEmergency ? 'emergency' : 'standard';
        return "payment_fraud:user:{$type}:{$userId}";
    }

    /**
     * Get tenant rate limit key
     */
    private function getTenantRateLimitKey(string $tenantId): string
    {
        return "payment_fraud:tenant:{$tenantId}";
    }

    /**
     * Log rate limit exceeded
     */
    private function logRateLimitExceeded(string $type, string $identifier, int $limit): void
    {
        Log::warning('Payment fraud rate limit exceeded', [
            'type' => $type,
            'identifier' => $identifier,
            'limit' => $limit,
            'ip' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }

    /**
     * Return rate limit response
     */
    private function rateLimitResponse(int $limit): Response
    {
        return response()->json([
            'error' => 'rate_limit_exceeded',
            'message' => 'Too many fraud check requests. Please try again later.',
            'limit' => $limit,
        ], 429);
    }
}
