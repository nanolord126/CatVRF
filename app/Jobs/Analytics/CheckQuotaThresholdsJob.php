<?php

declare(strict_types=1);

namespace App\Jobs\Analytics;

use App\Services\Analytics\QuotaClickHouseRepository;
use App\Services\Tenancy\TenantQuotaNotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Check Quota Thresholds and Send Alerts
 * 
 * Production 2026 CANON - Proactive Quota Monitoring
 * 
 * This job runs periodically (every minute) to:
 * - Check tenants approaching quota thresholds (85%, 95%, 100%)
 * - Send alerts via NotificationService
 * - Prevent hard limit violations by early warning
 * - Support multiple resource types (ai_tokens, llm_requests, etc.)
 * 
 * Run via scheduler: every minute
 */
final class CheckQuotaThresholdsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 120; // 2 minutes max

    private const WARNING_THRESHOLD = 85.0; // 85%
    private const CRITICAL_THRESHOLD = 95.0; // 95%
    private const EXCEEDED_THRESHOLD = 100.0; // 100%

    /**
     * Execute the job.
     */
    public function handle(
        QuotaClickHouseRepository $clickHouse,
        TenantQuotaNotificationService $notificationService
    ): void {
        try {
            $startTime = now();
            $warningCount = 0;
            $criticalCount = 0;
            $exceededCount = 0;

            // Get all tenants with quota limits
            $tenants = $this->getActiveTenants();
            $resourceTypes = ['ai_tokens', 'llm_requests', 'slot_holds', 'geo_queries', 'payment_attempts'];

            foreach ($tenants as $tenant) {
                foreach ($resourceTypes as $resourceType) {
                    $limit = $this->getTenantQuotaLimit($tenant->id, $resourceType);
                    
                    if ($limit === null || $limit === 0) {
                        continue; // Skip if no limit set
                    }

                    // Get current hour usage from ClickHouse
                    $currentUsage = $clickHouse->getCurrentHourUsage($tenant->id, $resourceType);
                    
                    // Calculate percentage
                    $percentage = $limit > 0 ? ($currentUsage / $limit) * 100 : 0;

                    // Check thresholds and send alerts
                    if ($percentage >= self::EXCEEDED_THRESHOLD) {
                        $this->sendAlert($tenant->id, $resourceType, $currentUsage, $limit, 'exceeded', $notificationService);
                        $exceededCount++;
                    } elseif ($percentage >= self::CRITICAL_THRESHOLD) {
                        $this->sendAlert($tenant->id, $resourceType, $currentUsage, $limit, 'critical', $notificationService);
                        $criticalCount++;
                    } elseif ($percentage >= self::WARNING_THRESHOLD) {
                        $this->sendAlert($tenant->id, $resourceType, $currentUsage, $limit, 'warning', $notificationService);
                        $warningCount++;
                    }
                }
            }

            $duration = now()->diffInSeconds($startTime);

            Log::info('Quota threshold check completed', [
                'tenants_checked' => count($tenants),
                'warning_count' => $warningCount,
                'critical_count' => $criticalCount,
                'exceeded_count' => $exceededCount,
                'duration_seconds' => $duration,
            ]);

        } catch (\Throwable $e) {
            Log::error('Failed to check quota thresholds', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    /**
     * Get active tenants
     */
    private function getActiveTenants(): array
    {
        return DB::table('tenants')
            ->where('is_active', true)
            ->select('id', 'name', 'business_group_id')
            ->get()
            ->toArray();
    }

    /**
     * Get quota limit for tenant and resource type
     * 
     * Uses QuotaLimit model to retrieve limits from database with fallback to defaults.
     */
    private function getTenantQuotaLimit(int $tenantId, string $resourceType): ?int
    {
        // Try to get limit from quota_limits table
        $limit = \App\Models\QuotaLimit::getEffectiveLimit($tenantId, $resourceType, 'hourly');

        if ($limit !== null) {
            return $limit;
        }

        // Fallback to default limits if not configured
        $defaultLimits = [
            'ai_tokens' => 1000000, // 1M tokens/hour
            'llm_requests' => 10000, // 10K requests/hour
            'slot_holds' => 5000, // 5K holds/hour
            'geo_queries' => 50000, // 50K queries/hour
            'payment_attempts' => 100, // 100 attempts/hour
        ];

        return $defaultLimits[$resourceType] ?? null;
    }

    /**
     * Send alert for quota threshold
     */
    private function sendAlert(
        int $tenantId,
        string $resourceType,
        float $currentUsage,
        int $limit,
        string $severity,
        TenantQuotaNotificationService $notificationService
    ): void {
        try {
            $percentage = $limit > 0 ? round(($currentUsage / $limit) * 100, 2) : 0;
            $quotaData = [
                'current_usage' => $currentUsage,
                'limit' => $limit,
                'percentage' => $percentage,
            ];

            Log::warning('Quota threshold alert', [
                'tenant_id' => $tenantId,
                'resource_type' => $resourceType,
                'severity' => $severity,
                'current_usage' => $currentUsage,
                'limit' => $limit,
                'percentage' => $percentage,
            ]);

            // Send notification based on severity
            match ($severity) {
                'warning' => $this->dispatch(new \App\Jobs\SendQuotaWarningJob($tenantId, $resourceType, $quotaData)),
                'critical' => $this->dispatch(new \App\Jobs\SendQuotaCriticalJob($tenantId, $resourceType, $quotaData)),
                'exceeded' => $notificationService->notifyQuotaExceeded($tenantId, $resourceType, $quotaData),
            };

        } catch (\Throwable $e) {
            Log::error('Failed to send quota alert', [
                'tenant_id' => $tenantId,
                'resource_type' => $resourceType,
                'severity' => $severity,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::critical('CheckQuotaThresholdsJob failed', [
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);

        // TODO: Send alert to monitoring system
    }
}
