<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Shared\Infrastructure\Persistence\ClickHouseEventStore;
use Illuminate\Bus\Batchable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Log\LogManager;
use Carbon\CarbonImmutable;

/**
 * Job to store published events to ClickHouse for long-term storage
 * Runs asynchronously to avoid impacting event processing performance
 */
final class StoreEventToClickHouseJob implements ShouldQueue
{
    use Batchable;
    use Dispatchable;
    use InteractsWithQueue;
    use SerializesModels;

    public string $queue = 'default';

    public int $tries = 3;

    public int $timeout = 60;

    public function __construct(
        private readonly string $outboxId,
        private readonly string $eventType,
        private readonly array $payload,
        private readonly ?string $correlationId = null,
        private readonly ?int $userId = null,
        private readonly ?int $tenantId = null,
        private readonly ?string $vertical = null,
        private readonly LogManager $log,
        private readonly ClickHouseEventStore $clickHouseStore,
    ) {}

    public function handle(): void
    {
        $clickHouseStore = $this->clickHouseStore;

        $eventData = [
            'event_id' => $this->outboxId,
            'event_type' => $this->eventType,
            'event_name' => $this->eventType,
            'payload' => $this->payload,
            'correlation_id' => $this->correlationId,
            'user_id' => $this->userId,
            'tenant_id' => $this->tenantId,
            'vertical' => $this->vertical ?? $this->extractVertical($this->eventType),
            'is_medical' => $this->isMedicalEvent($this->eventType),
            'is_financial' => $this->isFinancialEvent($this->eventType),
            'is_emergency' => $this->isEmergencyEvent($this->eventType),
            'data_classification' => $this->determineDataClassification($this->eventType),
            'occurred_at' => $this->payload['occurred_at'] ?? CarbonImmutable::now()->toDateTimeString(),
            'published_at' => CarbonImmutable::now()->toDateTimeString(),
            'source' => 'outbox',
            'publisher' => 'event_system',
            'status' => 'published',
        ];

        $success = $clickHouseStore->storeEvent($eventData);

        if (! $success) {
            $this->log->error('Failed to store event in ClickHouse', [
                'outbox_id' => $this->outboxId,
                'event_type' => $this->eventType,
            ]);
            $this->release(30); // Retry after 30 seconds
        }
    }

    public function failed(\Throwable $exception): void
    {
        $this->log->critical('StoreEventToClickHouseJob failed permanently', [
            'outbox_id' => $this->outboxId,
            'event_type' => $this->eventType,
            'error' => $exception->getMessage(),
        ]);
    }

    private function extractVertical(string $eventType): string
    {
        $eventTypeLower = strtolower($eventType);

        $verticals = [
            'medical', 'beauty', 'food', 'fashion', 'travel', 'auto', 'hotels',
            'real_estate', 'electronics', 'fitness', 'sports', 'luxury', 'insurance',
            'legal', 'logistics', 'education', 'payment', 'finances', 'pharmacy',
        ];

        foreach ($verticals as $vertical) {
            if (str_contains($eventTypeLower, $vertical)) {
                return $vertical;
            }
        }

        return 'unknown';
    }

    private function isMedicalEvent(string $eventType): bool
    {
        $eventTypeLower = strtolower($eventType);

        return str_contains($eventTypeLower, 'medical')
            || str_contains($eventTypeLower, 'health')
            || str_contains($eventTypeLower, 'pharmacy')
            || str_contains($eventTypeLower, 'veterinary');
    }

    private function isFinancialEvent(string $eventType): bool
    {
        $eventTypeLower = strtolower($eventType);

        return str_contains($eventTypeLower, 'payment')
            || str_contains($eventTypeLower, 'financial')
            || str_contains($eventTypeLower, 'transaction')
            || str_contains($eventTypeLower, 'invoice')
            || str_contains($eventTypeLower, 'refund');
    }

    private function isEmergencyEvent(string $eventType): bool
    {
        $eventTypeLower = strtolower($eventType);

        return str_contains($eventTypeLower, 'emergency')
            || str_contains($eventTypeLower, 'critical');
    }

    private function determineDataClassification(string $eventType): string
    {
        if ($this->isMedicalEvent($eventType)) {
            return 'medical';
        }

        if ($this->isFinancialEvent($eventType)) {
            return 'confidential';
        }

        return 'public';
    }
}
