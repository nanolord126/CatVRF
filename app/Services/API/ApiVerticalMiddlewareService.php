<?php

declare(strict_types=1);

namespace App\Services\Api;

use Illuminate\Http\Request;
use App\Services\RateLimit\ApiRateLimiterService;
use App\Traits\WithAuditLogging;
use App\Services\Audit\AuditService;

final class ApiVerticalMiddlewareService
{
    use WithAuditLogging;

    /**
     * Vertical-specific middleware configurations
     */
    private const VERTICAL_CONFIGS = [
        'medical' => [
            'requires_pii_guard' => true,
            'rate_limit_vertical' => 'medical',
            'required_abilities' => ['medical:diagnose', 'medical:appointments', 'medical:records'],
            'strict_rate_limit' => true,
        ],
        'beauty' => [
            'requires_pii_guard' => true,
            'rate_limit_vertical' => 'beauty',
            'required_abilities' => ['read', 'write'],
            'strict_rate_limit' => false,
        ],
        'food' => [
            'requires_pii_guard' => false,
            'rate_limit_vertical' => 'food',
            'required_abilities' => ['read', 'write'],
            'strict_rate_limit' => false,
        ],
        'payment' => [
            'requires_pii_guard' => true,
            'rate_limit_vertical' => 'payment',
            'required_abilities' => ['payment:initiate', 'wallet:read'],
            'strict_rate_limit' => true,
        ],
        'wallet' => [
            'requires_pii_guard' => true,
            'rate_limit_vertical' => 'wallet',
            'required_abilities' => ['wallet:read', 'wallet:write'],
            'strict_rate_limit' => true,
        ],
    ];

    public function __construct(
        private readonly ApiRateLimiterService $rateLimiter,
        private readonly AuditService $audit,
    ) {}

    /**
     * Get rate limiter instance (for middleware access)
     */
    public function getRateLimiter(): ApiRateLimiterService
    {
        return $this->rateLimiter;
    }

    /**
     * Detect vertical from request path
     */
    public function detectVertical(Request $request): ?string
    {
        $path = $request->path();

        // Match patterns like /api/v1/medical/*, /api/v1/beauty/*
        if (preg_match('#/api/v\d+/([^/]+)/#', $path, $matches)) {
            return $matches[1];
        }

        return null;
    }

    /**
     * Get middleware configuration for a vertical
     */
    public function getVerticalConfig(?string $vertical): array
    {
        return self::VERTICAL_CONFIGS[$vertical] ?? [
            'requires_pii_guard' => false,
            'rate_limit_vertical' => 'default',
            'required_abilities' => ['read'],
            'strict_rate_limit' => false,
        ];
    }

    /**
     * Check rate limit for vertical
     */
    public function checkRateLimit(Request $request, ?string $vertical = null): array
    {
        $vertical = $vertical ?? $this->detectVertical($request);
        $config = $this->getVerticalConfig($vertical);

        $endpointType = $config['strict_rate_limit'] ? 'strict' : null;

        return $this->rateLimiter->check(
            $request,
            $config['rate_limit_vertical'],
            $endpointType
        );
    }

    /**
     * Get required abilities for vertical
     */
    public function getRequiredAbilities(?string $vertical): array
    {
        $config = $this->getVerticalConfig($vertical);

        return $config['required_abilities'];
    }

    /**
     * Check if vertical requires PII guard
     */
    public function requiresPiiGuard(?string $vertical): bool
    {
        $config = $this->getVerticalConfig($vertical);

        return $config['requires_pii_guard'];
    }

    /**
     * Apply vertical-specific middleware stack
     *
     * @return array<string> Array of middleware names
     */
    public function getMiddlewareStack(?string $vertical): array
    {
        $config = $this->getVerticalConfig($vertical);
        $middleware = ['auth:sanctum', 'tenant'];

        if ($config['requires_pii_guard']) {
            $middleware[] = 'pii.guard';
        }

        $middleware[] = 'security.headers';

        return $middleware;
    }

    /**
     * Get all configured verticals
     */
    public function getConfiguredVerticals(): array
    {
        return array_keys(self::VERTICAL_CONFIGS);
    }
}
