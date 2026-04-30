<?php

declare(strict_types=1);

namespace App\Services\RateLimit;

use Psr\Log\LoggerInterface;

use Illuminate\Http\Request;
use Illuminate\Cache\RateLimiter;
use Illuminate\Log\LogManager;

final class ApiRateLimiterService
{
    private const CACHE_PREFIX = 'api_rate_limit:';

    /**
     * Rate limit configuration by vertical and endpoint type
     */
    private const RATE_LIMITS = [
        'default' => [
            'per_minute' => 60,
            'per_hour' => 1000,
        ],
        'medical' => [
            'diagnose' => [
                'per_minute' => 10,
                'per_hour' => 100,
            ],
            'appointments' => [
                'per_minute' => 30,
                'per_hour' => 500,
            ],
            'default' => [
                'per_minute' => 60,
                'per_hour' => 1000,
            ],
        ],
        'payment' => [
            'initiate' => [
                'per_minute' => 5,
                'per_hour' => 50,
            ],
            'default' => [
                'per_minute' => 30,
                'per_hour' => 500,
            ],
        ],
        'ai' => [
            'default' => [
                'per_minute' => 20,
                'per_hour' => 200,
            ],
        ],
    ];

    public function __construct(private readonly LoggerInterface $logger,
        private readonly RateLimiter $limiter,
        private readonly LogManager $log,) {}

    /**
     * Check rate limit for a request
     *
     * @return array{allowed: bool, retry_after: int|null, limit: int, remaining: int}
     */
    public function check(Request $request, ?string $vertical = null, ?string $endpointType = null): array
    {
        $userId = $request->user()?->id;
        $tenantId = $this->getTenantId($request);
        $ip = $request->ip();

        // Get rate limit configuration
        $limits = $this->getRateLimits($vertical, $endpointType);

        // Check per-user limit
        if ($userId !== null) {
            $userResult = $this->checkByKey($this->getUserKey($userId, $vertical, $endpointType), $limits);
            if (! $userResult['allowed']) {
                $this->log->warning('API rate limit exceeded (user)', [
                    'user_id' => $userId,
                    'tenant_id' => $tenantId,
                    'vertical' => $vertical,
                    'endpoint_type' => $endpointType,
                    'ip' => $ip,
                ]);

                return $userResult;
            }
        }

        // Check per-tenant limit
        if ($tenantId !== null) {
            $tenantResult = $this->checkByKey($this->getTenantKey($tenantId, $vertical), $limits);
            if (! $tenantResult['allowed']) {
                $this->log->warning('API rate limit exceeded (tenant)', [
                    'user_id' => $userId,
                    'tenant_id' => $tenantId,
                    'vertical' => $vertical,
                    'endpoint_type' => $endpointType,
                    'ip' => $ip,
                ]);

                return $tenantResult;
            }
        }

        // Check per-IP limit (as fallback for unauthenticated requests)
        $ipResult = $this->checkByKey($this->getIpKey($ip, $vertical), $limits);
        if (! $ipResult['allowed']) {
            $this->log->warning('API rate limit exceeded (IP)', [
                'user_id' => $userId,
                'tenant_id' => $tenantId,
                'vertical' => $vertical,
                'endpoint_type' => $endpointType,
                'ip' => $ip,
            ]);

            return $ipResult;
        }

        return $ipResult;
    }

    /**
     * Clear rate limits for a user
     */
    public function clearUserLimits(int $userId, ?string $vertical = null): void
    {
        $pattern = $this->getUserKey($userId, $vertical, '*');

        // This is a simplified implementation - in production, you'd use Redis pattern matching
        // or maintain a list of active keys for each user
        $this->log->$this->logger->info('Rate limits cleared for user', [
            'user_id' => $userId,
            'vertical' => $vertical,
        ]);
    }

    /**
     * Clear rate limits for a tenant
     */
    public function clearTenantLimits(int $tenantId, ?string $vertical = null): void
    {
        $this->log->$this->logger->info('Rate limits cleared for tenant', [
            'tenant_id' => $tenantId,
            'vertical' => $vertical,
        ]);
    }

    /**
     * Get rate limit headers for response
     */
    public function getRateLimitHeaders(array $result): array
    {
        return [
            'X-RateLimit-Limit' => (string) $result['limit'],
            'X-RateLimit-Remaining' => (string) $result['remaining'],
            'X-RateLimit-Reset' => $result['retry_after'] !== null
                ? (string) (time() + $result['retry_after'])
                : (string) (time() + 60),
        ];
    }

    /**
     * Get rate limit configuration
     */
    private function getRateLimits(?string $vertical, ?string $endpointType): array
    {
        if ($vertical !== null && isset(self::RATE_LIMITS[$vertical])) {
            if ($endpointType !== null && isset(self::RATE_LIMITS[$vertical][$endpointType])) {
                return self::RATE_LIMITS[$vertical][$endpointType];
            }

            return self::RATE_LIMITS[$vertical]['default'] ?? self::RATE_LIMITS['default'];
        }

        return self::RATE_LIMITS['default'];
    }

    /**
     * Check rate limit by key
     *
     * @return array{allowed: bool, retry_after: int|null, limit: int, remaining: int}
     */
    private function checkByKey(string $key, array $limits): array
    {
        $minuteKey = $key.':minute';
        $hourKey = $key.':hour';

        // Check per-minute limit
        if ($this->limiter->tooManyAttempts($minuteKey, $limits['per_minute'])) {
            return [
                'allowed' => false,
                'retry_after' => $this->limiter->availableIn($minuteKey),
                'limit' => $limits['per_minute'],
                'remaining' => 0,
            ];
        }

        // Check per-hour limit
        if ($this->limiter->tooManyAttempts($hourKey, $limits['per_hour'])) {
            return [
                'allowed' => false,
                'retry_after' => $this->limiter->availableIn($hourKey),
                'limit' => $limits['per_hour'],
                'remaining' => $this->limiter->remaining($hourKey, $limits['per_hour']),
            ];
        }

        // Increment counters
        $this->limiter->hit($minuteKey, 60); // 1 minute decay
        $this->limiter->hit($hourKey, 3600); // 1 hour decay

        return [
            'allowed' => true,
            'retry_after' => null,
            'limit' => $limits['per_minute'],
            'remaining' => $this->limiter->remaining($minuteKey, $limits['per_minute']),
        ];
    }

    /**
     * Get rate limit key for user
     */
    private function getUserKey(int $userId, ?string $vertical, ?string $endpointType): string
    {
        $parts = ['user', $userId];
        if ($vertical !== null) {
            $parts[] = $vertical;
        }
        if ($endpointType !== null) {
            $parts[] = $endpointType;
        }

        return self::CACHE_PREFIX.implode(':', $parts);
    }

    /**
     * Get rate limit key for tenant
     */
    private function getTenantKey(int $tenantId, ?string $vertical): string
    {
        $parts = ['tenant', $tenantId];
        if ($vertical !== null) {
            $parts[] = $vertical;
        }

        return self::CACHE_PREFIX.implode(':', $parts);
    }

    /**
     * Get rate limit key for IP
     */
    private function getIpKey(?string $ip, ?string $vertical): string
    {
        $parts = ['ip', $ip ?? 'unknown'];
        if ($vertical !== null) {
            $parts[] = $vertical;
        }

        return self::CACHE_PREFIX.implode(':', $parts);
    }

    /**
     * Get tenant ID from request
     */
    private function getTenantId(Request $request): ?int
    {
        // Try to get from tenant middleware or request attribute
        return $request->attributes->get('tenant_id');
    }
}
