<?php declare(strict_types=1);

namespace App\Http\Middleware;

use App\Exceptions\TenantQuotaExceededException;
use App\Services\Tenancy\TenantResourceLimiterService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Response;

/**
 * Tenant Quota Middleware
 *
 * Production 2026 CANON - Request-Level Quota Enforcement
 *
 * Enforces quota limits at the HTTP request level to prevent
 * resource exhaustion. Runs after TenantMiddleware but before
 * application logic to fail fast.
 *
 * @author CatVRF Team
 * @version 2026.04.17
 */
final class TenantQuotaMiddleware
{
    private const QUOTA_CHECK_INTERVAL = 10; // Check every 10th request to reduce Redis load

    public function __construct(
        private readonly TenantResourceLimiterService $quotaService,
        private readonly LoggerInterface $logger,
    ) {}

    /**
     * Handle the request
     */
    public function handle(Request $request, Closure $next): Response
    {
        $tenantId = $request->attributes->get('tenant_id');

        if (!$tenantId) {
            return $next($request);
        }

        try {
            // Check Redis quota (sampled to reduce load)
            if ($this->shouldCheckQuota()) {
                $this->quotaService->checkRedisQuota($tenantId);
            }

            // Check DB quota (sampled to reduce load)
            if ($this->shouldCheckQuota()) {
                $this->quotaService->checkDBQuota($tenantId);
            }

            return $next($request);
        } catch (TenantQuotaExceededException $e) {
            $this->logger->warning('Tenant quota exceeded at middleware level', [
                'tenant_id' => $tenantId,
                'resource_type' => $e->getResourceType(),
                'path' => $request->path(),
                'method' => $request->method(),
            ]);

            return $e->render($request);
        }
    }

    /**
     * Determine if quota should be checked (sampling)
     */
    private function shouldCheckQuota(): bool
    {
        return rand(1, self::QUOTA_CHECK_INTERVAL) === 1;
    }
}
