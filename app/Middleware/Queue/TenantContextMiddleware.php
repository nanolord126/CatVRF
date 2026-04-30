<?php

declare(strict_types=1);

namespace App\Middleware\Queue;

use TenancyService;

use Illuminate\Contracts\Queue\Job;
use Illuminate\Log\LogManager;
use Closure;
use App\Services\Tenancy\TenancyService;

/**
 * TenantContextMiddleware - Ensures tenant context is properly set for jobs
 *
 * CRITICAL: All multi-tenant jobs must have tenant context properly set
 * - Extracts tenant_id from job payload or tags
 * - Sets tenant context in tenancy service
 * - Logs warnings if tenant context is missing
 *
 * CatVRF 2026 - Production Ready
 */
final class TenantContextMiddleware
{
    public function __construct(private readonly TenancyService $tenancyService,
        private readonly LogManager $log,) {}
    public function handle(Job $job, Closure $next): void
    {
        $tenantId = $this->extractTenantId($job);

        if ($tenantId === null) {
            $this->log->warning('Job missing tenant context', [
                'job_class' => $job->resolveName(),
                'job_id' => $job->getJobId(),
                'payload' => $job->payload(),
            ]);

            // Proceed anyway for non-tenant jobs, but log warning
            $next($job);

            return;
        }

        // Set tenant context using tenancy service
        try {
            if (class_exists(TenancyService::class)) {
                $tenancyService = $this->tenancyService /* TODO: inject via constructor DI */ /* TODO: inject via DI */;
                $tenancyService->setTenantContext($tenantId);
            }

            $this->log->debug('Tenant context set for job', [
                'job_class' => $job->resolveName(),
                'job_id' => $job->getJobId(),
                'tenant_id' => $tenantId,
            ]);

            $next($job);

            // Clear tenant context after job completes
            if (class_exists(TenancyService::class)) {
                $tenancyService->clearTenantContext();
            }
        } catch (\Exception $e) {
            $this->log->error('Failed to set tenant context for job', [
                'job_class' => $job->resolveName(),
                'job_id' => $job->getJobId(),
                'tenant_id' => $tenantId,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Extract tenant_id from job payload or tags
     */
    private function extractTenantId(Job $job): ?string
    {
        $payload = $job->payload();

        // Try to get from payload data
        if (isset($payload['data']['tenant_id'])) {
            return (string) $payload['data']['tenant_id'];
        }

        if (isset($payload['data']['command'])) {
            $command = unserialize($payload['data']['command']);

            // Try to get from command properties
            if (isset($command->tenantId)) {
                return (string) $command->tenantId;
            }

            if (isset($command->tenant_id)) {
                return (string) $command->tenant_id;
            }

            // Try to get from DTO properties
            if (isset($command->dto) && isset($command->dto->tenant_id)) {
                return (string) $command->dto->tenant_id;
            }
        }

        // Try to get from job tags
        $tags = method_exists($job, 'tags') ? $job->tags() : [];
        foreach ($tags as $tag) {
            if (str_starts_with($tag, 'tenant:')) {
                return substr($tag, 7);
            }
        }

        return null;
    }
}
