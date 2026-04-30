<?php

declare(strict_types=1);

namespace Modules\Analytics\Application\DTOs;

/**
 * Experiment Data Transfer Object
 *
 * Immutable DTO containing experiment configuration and metadata.
 * Production-ready: readonly properties, strict typing, validation.
 */
final readonly class ExperimentDTO
{
    public function __construct(
        public ?int $id,
        public ?int $tenantId,
        public ?int $sellerId,
        public string $key,
        public string $name,
        public ?string $description,
        public string $targetSegment,
        public ?array $clvFilters,
        public int $trafficPercent,
        public ?\DateTimeImmutable $startedAt,
        public ?\DateTimeImmutable $endedAt,
        public ?\DateTimeImmutable $scheduledStartAt,
        public ?\DateTimeImmutable $scheduledEndAt,
        public string $status,
        public string $primaryMetric,
        public ?array $secondaryMetrics,
        public ?array $results,
        public ?string $winningVariantId,
        public ?array $metadata,
        public ?int $createdBy,
    ) {}

    /**
     * Create ExperimentDTO from array data.
     */
    public static function fromArray(array $data): self
    {
        return new self(
            id: $data['id'] ?? null,
            tenantId: $data['tenant_id'] ?? null,
            sellerId: $data['seller_id'] ?? null,
            key: $data['key'],
            name: $data['name'],
            description: $data['description'] ?? null,
            targetSegment: $data['target_segment'],
            clvFilters: $data['clv_filters'] ?? null,
            trafficPercent: (int) ($data['traffic_percent'] ?? 100),
            startedAt: isset($data['started_at']) 
                ? \DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $data['started_at']) 
                : null,
            endedAt: isset($data['ended_at']) 
                ? \DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $data['ended_at']) 
                : null,
            scheduledStartAt: isset($data['scheduled_start_at']) 
                ? \DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $data['scheduled_start_at']) 
                : null,
            scheduledEndAt: isset($data['scheduled_end_at']) 
                ? \DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $data['scheduled_end_at']) 
                : null,
            status: $data['status'] ?? 'draft',
            primaryMetric: $data['primary_metric'] ?? 'revenue_14d',
            secondaryMetrics: $data['secondary_metrics'] ?? null,
            results: $data['results'] ?? null,
            winningVariantId: $data['winning_variant_id'] ?? null,
            metadata: $data['metadata'] ?? null,
            createdBy: $data['created_by'] ?? null,
        );
    }

    /**
     * Convert to array for JSON serialization.
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'tenant_id' => $this->tenantId,
            'seller_id' => $this->sellerId,
            'key' => $this->key,
            'name' => $this->name,
            'description' => $this->description,
            'target_segment' => $this->targetSegment,
            'clv_filters' => $this->clvFilters,
            'traffic_percent' => $this->trafficPercent,
            'started_at' => $this->startedAt?->format('Y-m-d H:i:s'),
            'ended_at' => $this->endedAt?->format('Y-m-d H:i:s'),
            'scheduled_start_at' => $this->scheduledStartAt?->format('Y-m-d H:i:s'),
            'scheduled_end_at' => $this->scheduledEndAt?->format('Y-m-d H:i:s'),
            'status' => $this->status,
            'primary_metric' => $this->primaryMetric,
            'secondary_metrics' => $this->secondaryMetrics,
            'results' => $this->results,
            'winning_variant_id' => $this->winningVariantId,
            'metadata' => $this->metadata,
            'created_by' => $this->createdBy,
        ];
    }

    /**
     * Check if experiment is running.
     */
    public function isRunning(): bool
    {
        return $this->status === 'running';
    }

    /**
     * Check if experiment is draft.
     */
    public function isDraft(): bool
    {
        return $this->status === 'draft';
    }

    /**
     * Check if experiment is finished.
     */
    public function isFinished(): bool
    {
        return $this->status === 'finished';
    }

    /**
     * Check if experiment is platform-wide (no seller).
     */
    public function isPlatformWide(): bool
    {
        return $this->sellerId === null;
    }
}
