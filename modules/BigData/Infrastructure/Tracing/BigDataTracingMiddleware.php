<?php

declare(strict_types=1);

namespace Modules\BigData\Infrastructure\Tracing;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Context;
use Illuminate\Support\Facades\Log;
use Modules\BigData\Infrastructure\Tracing\BigDataTracingService;

/**
 * BigData OpenTelemetry Middleware
 *
 * Auto-instruments Laravel HTTP requests that hit BigData endpoints.
 * Propagates correlation IDs and trace context through the pipeline.
 *
 * Register in bootstrap/app.php or RouteServiceProvider:
 *   $middleware->appendToGroup('api', [BigDataTracingMiddleware::class]);
 */
final readonly class BigDataTracingMiddleware
{
    public function __construct(
        private readonly BigDataTracingService $tracingService,
    ) {}

    public function handle(Request $request, Closure $next)
    {
        // Only instrument BigData API routes
        if (!$this->isBigDataRoute($request)) {
            return $next($request);
        }

        $correlationId = $this->tracingService->getOrCreateCorrelationId();

        // Store correlation ID in request attributes for downstream usage
        $request->attributes->set('correlation_id', $correlationId);
        $request->headers->set('X-Correlation-ID', $correlationId);

        // Start OTel span
        $span = $this->tracingService->startEventTrackingSpan(
            eventType: 'http.' . $request->method(),
            correlationId: $correlationId,
        );

        // Add HTTP attributes to span
        if ($span) {
            $this->addHttpAttributes($span, $request, $correlationId);

            // Activate span in OTel context
            try {
                $context = \OpenTelemetry\Context\Context::getCurrent()
                    ->withSpan($span);
                $scope = $context->activate();
            } catch (\Throwable $e) {
                Log::debug('OTel: failed to activate span context', [
                    'error' => $e->getMessage(),
                ]);
                $scope = null;
            }
        }

        $startTime = microtime(true);

        try {
            $response = $next($request);

            // Add response attributes to span
            if ($span) {
                $this->addResponseAttributes($span, $response);
            }

            // Propagate correlation ID in response
            $response->headers->set('X-Correlation-ID', $correlationId);

            return $response;
        } catch (\Exception $e) {
            $this->tracingService->endSpan($span, false, $e->getMessage());

            throw $e;
        } finally {
            if (isset($scope)) {
                try {
                    $scope->detach();
                } catch (\Throwable $e) {
                    // Ignore
                }
            }

            $duration = round((microtime(true) - $startTime) * 1000, 2);
            $this->tracingService->addSpanEvent($span, 'request_completed', [
                'duration_ms' => $duration,
            ]);

            $this->tracingService->endSpan($span);
        }
    }

    /**
     * Add HTTP request attributes to span
     */
    private function addHttpAttributes(object $span, Request $request, string $correlationId): void
    {
        try {
            $span->setAttribute('http.method', $request->method());
            $span->setAttribute('http.url', $request->fullUrl());
            $span->setAttribute('http.target', $request->getRequestUri());
            $span->setAttribute('http.scheme', $request->getScheme());
            $span->setAttribute('http.host', $request->getHost());
            $span->setAttribute('correlation.id', $correlationId);

            // Add tenant context if available
            $tenantId = $request->header('X-Tenant-ID');
            if ($tenantId) {
                $span->setAttribute('tenant.id', $tenantId);
            }

            // Add user context if authenticated
            $userId = $request->user()?->id;
            if ($userId) {
                $span->setAttribute('user.id', (string) $userId);
            }
        } catch (\Throwable $e) {
            Log::debug('OTel: failed to add HTTP attributes', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Add HTTP response attributes to span
     */
    private function addResponseAttributes(object $span, mixed $response): void
    {
        try {
            if (method_exists($response, 'getStatusCode')) {
                $statusCode = $response->getStatusCode();
                $span->setAttribute('http.status_code', $statusCode);

                if ($statusCode >= 400) {
                    $span->setAttribute('error', true);

                    if ($statusCode >= 500) {
                        $span->setAttribute('error.type', 'server_error');
                    } else {
                        $span->setAttribute('error.type', 'client_error');
                    }
                }
            }
        } catch (\Throwable $e) {
            Log::debug('OTel: failed to add response attributes', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Check if this request targets a BigData route
     */
    private function isBigDataRoute(Request $request): bool
    {
        $path = $request->path();

        return str_starts_with($path, 'api/bigdata')
            || str_starts_with($path, 'metrics/bigdata')
            || str_starts_with($path, 'api/seller')
            || str_starts_with($path, 'api/clv')
            || str_starts_with($path, 'api/abtest')
            || str_starts_with($path, 'api/bigdata/monitoring');
    }
}
