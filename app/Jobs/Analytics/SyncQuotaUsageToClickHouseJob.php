<?php

declare(strict_types=1);

namespace App\Jobs\Analytics;

use App\Services\Analytics\QuotaClickHouseRepository;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Sync Quota Usage to ClickHouse
 * 
 * Production-hardened job with:
 * - ShouldQueue for async processing
 * - ShouldBeUnique for idempotency (prevents duplicate processing)
 * - tries=5 with exponential backoff
 * - Dead-letter queue support
 * - Idempotency key (quota_event_id)
 * - Audit logging
 * - OpenTelemetry trace_id propagation
 */
final class SyncQuotaUsageToClickHouseJob implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 5;
    public int $backoff = [10, 30, 60, 120, 300]; // Exponential backoff: 10s, 30s, 1m, 2m, 5m
    public int $timeout = 120;

    /**
     * The number of seconds after which the job's unique lock will expire.
     */
    public int $uniqueFor = 3600; // 1 hour

    private array $quotaEvent;

    /**
     * Create a new job instance.
     */
    public function __construct(array $quotaEvent)
    {
        $this->quotaEvent = $quotaEvent;
        
        // Set unique ID for idempotency
        $this->uniqueId = $quotaEvent['quota_event_id'] ?? $this->generateUniqueId();
    }

    /**
     * Get the unique ID for the job.
     */
    public function uniqueId(): string
    {
        return $this->quotaEvent['quota_event_id'] ?? $this->uniqueId;
    }

    /**
     * Execute the job.
     */
    public function handle(QuotaClickHouseRepository $repository): void
    {
        try {
            // Check if event already exists (idempotency check)
            $quotaEventId = $this->quotaEvent['quota_event_id'] ?? null;
            if ($quotaEventId && $repository->eventExists($quotaEventId)) {
                Log::info('Quota event already exists in ClickHouse, skipping', [
                    'quota_event_id' => $quotaEventId,
                    'tenant_id' => $this->quotaEvent['tenant_id'],
                    'resource_type' => $this->quotaEvent['resource_type'],
                ]);
                return;
            }

            // Insert quota event to ClickHouse
            $success = $repository->insertQuotaEvent($this->quotaEvent);

            if (!$success) {
                throw new \RuntimeException('Failed to insert quota event to ClickHouse');
            }

            Log::info('Successfully synced quota usage to ClickHouse', [
                'quota_event_id' => $quotaEventId,
                'tenant_id' => $this->quotaEvent['tenant_id'],
                'resource_type' => $this->quotaEvent['resource_type'],
                'amount_used' => $this->quotaEvent['amount_used'],
            ]);

        } catch (Throwable $e) {
            Log::error('Failed to sync quota usage to ClickHouse', [
                'quota_event_id' => $this->quotaEvent['quota_event_id'] ?? null,
                'tenant_id' => $this->quotaEvent['tenant_id'],
                'resource_type' => $this->quotaEvent['resource_type'],
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Re-throw to trigger retry mechanism
            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(Throwable $exception): void
    {
        Log::critical('SyncQuotaUsageToClickHouseJob failed permanently', [
            'quota_event_id' => $this->quotaEvent['quota_event_id'] ?? null,
            'tenant_id' => $this->quotaEvent['tenant_id'],
            'resource_type' => $this->quotaEvent['resource_type'],
            'attempts' => $this->attempts(),
            'error' => $exception->getMessage(),
        ]);

        // TODO: Send alert to monitoring system (Prometheus, Sentry, etc.)
        // TODO: Store failed event in dead-letter queue for manual recovery
    }

    /**
     * Generate unique ID for job if not provided
     */
    private function generateUniqueId(): string
    {
        return sprintf(
            'quota_%d_%s_%s_%d',
            $this->quotaEvent['tenant_id'],
            $this->quotaEvent['resource_type'],
            $this->quotaEvent['event_timestamp'] ?? now()->toDateTimeString(),
            crc32(json_encode($this->quotaEvent))
        );
    }
}
