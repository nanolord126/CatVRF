<?php

declare(strict_types=1);
use App\Http\Middleware\CorrelationIdMiddleware;
use App\Http\Middleware\EnrichRequestContextMiddleware;
use App\Http\Middleware\FraudCheckMiddleware;
use App\Http\Middleware\RateLimitMiddleware;
use App\Http\Middleware\TenantMiddleware;
use App\Http\Middleware\WebhookSignatureMiddleware;

/**
 * Middleware Configuration
 * Production 2026 CANON
 *
 * Middleware pipeline for API requests:
 * 1. CorrelationIdMiddleware - inject/validate correlation_id
 * 2. EnrichRequestContextMiddleware - add IP, user_agent, timing
 * 3. auth:sanctum - validate API token (Laravel built-in)
 * 4. TenantMiddleware - validate and scope tenant
 * 5. RateLimitMiddleware - per-endpoint throttling
 * 6. FraudCheckMiddleware - pre-payment fraud checks
 * 7. WebhookSignatureMiddleware - validate payment gateway webhooks
 *
 * @author CatVRF Team
 *
 * @version 2026.03.25
 */

return [
    // Middleware aliases
    'aliases' => [
        'correlation-id' => CorrelationIdMiddleware::class,
        'enrich-context' => EnrichRequestContextMiddleware::class,
        'tenant' => TenantMiddleware::class,
        'rate-limit' => RateLimitMiddleware::class,
        'fraud-check' => FraudCheckMiddleware::class,
        'webhook-signature' => WebhookSignatureMiddleware::class,
    ],

    // Groups for common use
    'groups' => [
        'api' => [
            'correlation-id',
            'enrich-context',
            'auth:sanctum',
            'tenant',
            'rate-limit',
        ],

        'api.payment' => [
            'correlation-id',
            'enrich-context',
            'auth:sanctum',
            'tenant',
            'rate-limit',
            'fraud-check',
        ],

        'webhooks' => [
            'correlation-id',
            'enrich-context',
            'webhook-signature',
        ],

        'public' => [
            'correlation-id',
            'enrich-context',
        ],
    ],

    // Per-endpoint middleware assignments
    'endpoints' => [
        // Payment operations - require fraud checks
        'POST /api/v1/payment/init' => 'api.payment',
        'POST /api/v1/payment/capture' => 'api.payment',
        'POST /api/v1/payment/refund' => 'api.payment',

        // Regular API endpoints
        'POST /api/v1/*/create' => 'api',
        'PUT /api/v1/*/update' => 'api',
        'GET /api/v1/*' => 'api',

        // Webhooks - signature validation only
        'POST /webhooks/tinkoff' => 'webhooks',
        'POST /webhooks/tochka' => 'webhooks',
        'POST /webhooks/sber' => 'webhooks',

        // Public endpoints - minimal middleware
        'GET /health' => 'public',
    ],
];
