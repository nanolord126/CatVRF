<?php

declare(strict_types=1);

namespace Modules\Analytics\Application\DTOs;

use Carbon\CarbonImmutable;
use Modules\Analytics\Domain\ValueObjects\MetricType;

/**
 * Metric Data DTO
 *
 * Represents a single metric data point with value and timestamp.
 * Immutable DTO for analytics responses.
 */
final readonly class MetricDataDto
{
    public function __construct(
        public readonly MetricType $metricType,
        public readonly float|int $value,
        public readonly CarbonImmutable $timestamp,
        public readonly ?int $tenantId = null,
        public readonly ?int $sellerId = null,
        public readonly ?int $productId = null,
        public readonly array $dimensions = [],
    ) {}

    public static function create(
        MetricType $metricType,
        float|int $value,
        CarbonImmutable $timestamp,
        ?int $tenantId = null,
        ?int $sellerId = null,
        ?int $productId = null,
        array $dimensions = [],
    ): self {
        return new self(
            $metricType,
            $value,
            $timestamp,
            $tenantId,
            $sellerId,
            $productId,
            $dimensions,
        );
    }

    public function toArray(): array
    {
        return [
            'metric_type' => (string) $this->metricType,
            'value' => $this->value,
            'timestamp' => $this->timestamp->toIso8601String(),
            'tenant_id' => $this->tenantId,
            'seller_id' => $this->sellerId,
            'product_id' => $this->productId,
            'dimensions' => $this->dimensions,
        ];
    }

    public static function fromArray(array $data): self
    {
        return new self(
            MetricType::fromString($data['metric_type']),
            $data['value'],
            CarbonImmutable::parse($data['timestamp']),
            $data['tenant_id'] ?? null,
            $data['seller_id'] ?? null,
            $data['product_id'] ?? null,
            $data['dimensions'] ?? [],
        );
    }
}
