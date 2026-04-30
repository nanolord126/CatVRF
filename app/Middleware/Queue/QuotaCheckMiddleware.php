<?php

declare(strict_types=1);

namespace App\Middleware\Queue;

use TenantQuotaService;

use Illuminate\Contracts\Queue\Job;
use Illuminate\Log\LogManager;
use Closure;
use App\Services\TenantQuotaService;

/**
 * QuotaCheckMiddleware - Checks tenant quota before executing heavy jobs
 *
 * CRITICAL: Prevents quota exhaustion by checking before resource-intensive operations
 * - Skips quota check for emergency and notification queues (critical path)
 * - Checks tenant quota before job execution
 * - Logs warnings and fails job if quota exceeded
 * - Configurable via environment variable QUOTA_CHECK_ENABLED
 *
 * CatVRF 2026 - Production Ready
 */
final class QuotaCheckMiddleware
{
    private readonly bool $quotaCheckEnabled;

    public function __construct(private readonly TenantQuotaService $tenantQuotaService,
        private readonly LogManager $log,) {
        $this->quotaCheckEnabled = config('quota.check_enabled', true);
    }

    public function handle(Job $job, Closure $next): void
    {
        // Skip quota check for critical queues
        if ($this->shouldSkipQuotaCheck($job)) {
            $next($job);

            return;
        }

        if (! $this->quotaCheckEnabled) {
            $next($job);

            return;
        }

        $tenantId = $this->extractTenantId($job);

        if ($tenantId === null) {
            $this->log->warning('Quota check skipped - no tenant context', [
                'job_class' => $job->resolveName(),
                'job_id' => $job->getJobId(),
            ]);

            $next($job);

            return;
        }

        // Check quota using TenantQuotaService
        if (! $this->checkQuota($tenantId, $job)) {
            $this->handleQuotaExceeded($job, $tenantId);

            return;
        }

        $next($job);
    }

    /**
     * Determine if quota check should be skipped for this job
     */
    private function shouldSkipQuotaCheck(Job $job): bool
    {
        $queue = $job->getQueue();

        // Skip for critical queues
        $skipQueues = ['emergency', 'notification', 'payment-webhook', 'fraud-check-payment'];

        return in_array($queue, $skipQueues, true);
    }

    /**
     * Check if tenant has sufficient quota
     */
    private function checkQuota(string $tenantId, Job $job): bool
    {
        try {
            if (! class_exists(TenantQuotaService::class)) {
                return true; // Skip check if service doesn't exist
            }

            $quotaService = $this->tenantQuotaService /* TODO: inject via constructor DI */ /* TODO: inject via DI */;

            // Get quota usage for tenant
            $usage = $quotaService->getCurrentUsage($tenantId);
            $limit = $quotaService->getLimit($tenantId);

            // Allow job if usage is below 90% of limit
            $threshold = $limit * 0.9;

            if ($usage >= $threshold) {
                $this->log->warning('Tenant quota approaching limit', [
                    'tenant_id' => $tenantId,
                    'usage' => $usage,
                    'limit' => $limit,
                    'threshold' => $threshold,
                    'job_class' => $job->resolveName(),
                ]);

                return false;
            }

            return true;
        } catch (\Exception $e) {
            $this->log->error('Failed to check tenant quota', [
                'tenant_id' => $tenantId,
                'error' => $e->getMessage(),
                'job_class' => $job->resolveName(),
            ]);

            // Allow job to proceed on quota check failure to prevent blocking
            return true;
        }
    }

    /**
     * Handle quota exceeded scenario
     */
    private function handleQuotaExceeded(Job $job, string $tenantId): void
    {
        $this->log->error('Job failed - tenant quota exceeded', [
            'tenant_id' => $tenantId,
            'job_class' => $job->resolveName(),
            'job_id' => $job->getJobId(),
        ]);

        // Release job back to queue with delay (retry later)
        $job->release(config('quota.retry_delay', 300)); // 5 minutes default
    }

    /**
     * Extract tenant_id from job payload
     */
    private function extractTenantId(Job $job): ?string
    {
        $payload = $job->payload();

        if (isset($payload['data']['tenant_id'])) {
            return (string) $payload['data']['tenant_id'];
        }

        if (isset($payload['data']['command'])) {
            $command = unserialize($payload['data']['command']);

            if (isset($command->tenantId)) {
                return (string) $command->tenantId;
            }

            if (isset($command->tenant_id)) {
                return (string) $command->tenant_id;
            }

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
