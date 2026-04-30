<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Cache\RateLimiter;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;
use Psr\Log\LoggerInterface;

/**
 * WMS Rate Limiter Middleware
 *
 * Implements rate limiting for WMS API endpoints to prevent abuse and ensure
 * system stability. Different limits apply to different operation types.
 *
 * @author CatVRF Team
 * @version 2026.04.28
 */
final readonly class WMSRateLimiter
{
    private const DEFAULT_LIMIT = 60; // requests per minute
    private const STOCK_MOVEMENT_LIMIT = 100; // requests per minute
    private const BATCH_OPERATIONS_LIMIT = 50; // requests per minute
    private const REPORTS_LIMIT = 20; // requests per minute
    private const INTEGRATION_LIMIT = 30; // requests per minute

    public function __construct(
        private readonly RateLimiter $limiter,
        private readonly LoggerInterface $logger,
    ) {}

    public function handle(Request $request, Closure $next, string $limitType = 'default'): SymfonyResponse
    {
        $key = $this->resolveRequestSignature($request, $limitType);
        
        $limit = $this->getLimitForType($limitType);
        $decaySeconds = 60; // 1 minute window

        if ($this->limiter->tooManyAttempts($key, $limit)) {
            $this->logger->warning('Rate limit exceeded', [
                'ip' => $request->ip(),
                'user_id' => $request->user()?->id,
                'limit_type' => $limitType,
                'limit' => $limit,
                'path' => $request->path(),
            ]);

            return $this->buildResponse($key, $limit);
        }

        $this->limiter->hit($key, $decaySeconds);

        $response = $next($request);

        // Add rate limit headers
        $response->headers->set('X-RateLimit-Limit', (string) $limit);
        $response->headers->set('X-RateLimit-Remaining', (string) $this->limiter->remaining($key, $limit));

        return $response;
    }

    /**
     * Resolve request signature for rate limiting
     */
    private function resolveRequestSignature(Request $request, string $limitType): string
    {
        $userId = $request->user()?->id;
        $ipAddress = $request->ip();
        $tenantId = $request->user()?->tenant_id ?? 'anonymous';

        return sha1(
            implode('|', [
                $limitType,
                $userId ?? 'anonymous',
                $tenantId,
                $ipAddress,
                $request->route()?->getName() ?? $request->path(),
            ])
        );
    }

    /**
     * Get rate limit based on operation type
     */
    private function getLimitForType(string $limitType): int
    {
        return match ($limitType) {
            'stock_movement' => self::STOCK_MOVEMENT_LIMIT,
            'batch_operations' => self::BATCH_OPERATIONS_LIMIT,
            'reports' => self::REPORTS_LIMIT,
            'integration' => self::INTEGRATION_LIMIT,
            default => self::DEFAULT_LIMIT,
        };
    }

    /**
     * Build rate limit exceeded response
     */
    private function buildResponse(string $key, int $limit): SymfonyResponse
    {
        $retryAfter = $this->limiter->availableIn($key);

        return response()->json([
            'error' => 'RATE_LIMIT_EXCEEDED',
            'message' => 'Too many requests. Please try again later.',
            'retry_after' => $retryAfter,
            'limit' => $limit,
        ], Response::HTTP_TOO_MANY_REQUESTS)->header('Retry-After', (string) $retryAfter);
    }
}
