<?php

declare(strict_types=1);

namespace Modules\Analytics\Application\DTOs;

use Carbon\CarbonImmutable;
use Modules\Analytics\Domain\ValueObjects\Period;

/**
 * Aggregated Metrics DTO
 *
 * Represents aggregated metrics for a period (sum, avg, min, max, count).
 * Used for KPI cards and summary statistics.
 */
final readonly class AggregatedMetricsDto
{
    /**
     * @param array<string, float|int> $metrics
     */
    public function __construct(
        public readonly Period $period,
        public readonly array $metrics,
        public readonly CarbonImmutable $calculatedAt,
        public readonly ?int $tenantId = null,
        public readonly ?int $sellerId = null,
        public readonly array $comparison = [],
    ) {}

    /**
     * @param array<string, float|int> $metrics
     */
    public static function create(
        Period $period,
        array $metrics,
        ?int $tenantId = null,
        ?int $sellerId = null,
        array $comparison = [],
    ): self {
        return new self(
            $period,
            $metrics,
            CarbonImmutable::now(),
            $tenantId,
            $sellerId,
            $comparison,
        );
    }

    public function getMetric(string $key): float|int|null
    {
        return $this->metrics[$key] ?? null;
    }

    public function hasMetric(string $key): bool
    {
        return isset($this->metrics[$key]);
    }

    public function getComparison(string $key): float|int|null
    {
        return $this->comparison[$key] ?? null;
    }

    public function getGrowthRate(string $key): float|null
    {
        if (!$this->hasMetric($key) || !isset($this->comparison[$key])) {
            return null;
        }

        $current = (float) $this->metrics[$key];
        $previous = (float) $this->comparison[$key];

        if ($previous === 0.0) {
            return $current > 0 ? 100.0 : 0.0;
        }

        return (($current - $previous) / $previous) * 100;
    }

    public function toArray(): array
    {
        return [
            'period' => (string) $this->period,
            'period_from' => $this->period->from()->toIso8601String(),
            'period_to' => $this->period->to()->toIso8601String(),
            'metrics' => $this->metrics,
            'comparison' => $this->comparison,
            'calculated_at' => $this->calculatedAt->toIso8601String(),
            'tenant_id' => $this->tenantId,
            'seller_id' => $this->sellerId,
        ];
    }
}
