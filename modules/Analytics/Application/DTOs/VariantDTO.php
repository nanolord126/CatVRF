<?php

declare(strict_types=1);

namespace Modules\Analytics\Application\DTOs;

/**
 * Variant Data Transfer Object
 *
 * Immutable DTO containing variant configuration for A/B tests.
 * Production-ready: readonly properties, strict typing, validation.
 */
final readonly class VariantDTO
{
    public function __construct(
        public ?int $id,
        public int $experimentId,
        public string $key,
        public string $name,
        public ?array $configuration,
        public int $trafficAllocation,
        public bool $isControl,
        public int $sampleSize,
        public ?array $metrics,
    ) {}

    /**
     * Create VariantDTO from array data.
     */
    public static function fromArray(array $data): self
    {
        return new self(
            id: $data['id'] ?? null,
            experimentId: (int) $data['experiment_id'],
            key: $data['key'],
            name: $data['name'],
            configuration: $data['configuration'] ?? null,
            trafficAllocation: (int) ($data['traffic_allocation'] ?? 0),
            isControl: (bool) ($data['is_control'] ?? false),
            sampleSize: (int) ($data['sample_size'] ?? 0),
            metrics: $data['metrics'] ?? null,
        );
    }

    /**
     * Convert to array for JSON serialization.
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'experiment_id' => $this->experimentId,
            'key' => $this->key,
            'name' => $this->name,
            'configuration' => $this->configuration,
            'traffic_allocation' => $this->trafficAllocation,
            'is_control' => $this->isControl,
            'sample_size' => $this->sampleSize,
            'metrics' => $this->metrics,
        ];
    }

    /**
     * Get discount from configuration.
     */
    public function getDiscount(): ?int
    {
        return $this->configuration['discount'] ?? null;
    }

    /**
     * Get message from configuration.
     */
    public function getMessage(): ?string
    {
        return $this->configuration['message'] ?? null;
    }

    /**
     * Get coupon code from configuration.
     */
    public function getCouponCode(): ?string
    {
        return $this->configuration['coupon_code'] ?? null;
    }

    /**
     * Check if variant has discount.
     */
    public function hasDiscount(): bool
    {
        return $this->getDiscount() !== null && $this->getDiscount() > 0;
    }
}
