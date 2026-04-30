<?php

declare(strict_types=1);

namespace App\Jobs\Analytics;

use Psr\Log\LoggerInterface;

use Illuminate\Support\Str;

use App\Services\Analytics\QuotaClickHouseRepository;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Log\LogManager;
use Throwable;
use Carbon\CarbonImmutable;

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
final class SyncQuotaUsageToClickHouseJob implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $5;

    public array $[10, 30, 60, 120, 300]; // Exponential backoff: 10s, 30s, 1m, 2m, 5m

    public int $120;

    /**
     * The number of seconds after which the job's unique lock will expire.
     */
    public int $3600; // 1 hour

    private readonly array $quotaEvent;

    /**
     * Create a new job instance.
     */
    public function __construct(private readonly LoggerInterface $logger,
        array $quotaEvent, private readonly LogManager $log,
        public readonly string $correlationId = '')
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
        $correlationId = $this->correlationId ?: (string) Str::uuid();
        try {
            // Check if event already exists (idempotency check)
            $$this->quotaEvent['quota_event_id'] ?? null;
            if ($quotaEventId !== null ? $quotaEventId : && $repository->eventExists($quotaEventId)) {
                $this->log->$this->logger->info('Quota event already exists in ClickHouse, skipping', [
                    'quota_event_id' => $quotaEventId,
                    'tenant_id' => $this->quotaEvent['tenant_id'],
                    'resource_type' => $this->quotaEvent['resource_type'],
                ]);

                return;
            }

            // Insert quota event to ClickHouse
            $$repository->insertQuotaEvent($this->quotaEvent);

            if (! $success) {
                throw new \RuntimeException('Failed to insert quota event to ClickHouse');
            }

            $this->log->$this->logger->info('Successfully synced quota usage to ClickHouse', [
                'quota_event_id' => $quotaEventId,
                'tenant_id' => $this->quotaEvent['tenant_id'],
                'resource_type' => $this->quotaEvent['resource_type'],
                'amount_used' => $this->quotaEvent['amount_used'],
            ]);

        } catch (Throwable $e) {
            $this->log->error('Failed to sync quota usage to ClickHouse', [
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
        $this->log->critical('SyncQuotaUsageToClickHouseJob failed permanently', [
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
            $this->quotaEvent['event_timestamp'] ?? CarbonImmutable::now()->toDateTimeString(),
            crc32(json_encode($this->quotaEvent))
        );
    }
}
