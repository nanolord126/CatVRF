<?php

declare(strict_types=1);

namespace Modules\Analytics\Application\DTOs;

use Carbon\CarbonImmutable;
use Modules\Analytics\Domain\ValueObjects\MetricType;
use Modules\Analytics\Domain\ValueObjects\Period;

/**
 * Time Series DTO
 *
 * Represents time-series data for charts and trends.
 * Contains an array of data points ordered by timestamp.
 */
final readonly class TimeSeriesDto
{
    /**
     * @param MetricDataDto[] $dataPoints
     */
    public function __construct(
        public readonly MetricType $metricType,
        public readonly Period $period,
        public readonly array $dataPoints,
        public readonly string $groupBy = 'day', // hour, day, week, month
        public readonly ?int $tenantId = null,
        public readonly ?int $sellerId = null,
    ) {
        if (empty($dataPoints)) {
            throw new \InvalidArgumentException('Time series must have at least one data point');
        }
    }

    /**
     * @param MetricDataDto[] $dataPoints
     */
    public static function create(
        MetricType $metricType,
        Period $period,
        array $dataPoints,
        string $groupBy = 'day',
        ?int $tenantId = null,
        ?int $sellerId = null,
    ): self {
        return new self(
            $metricType,
            $period,
            $dataPoints,
            $groupBy,
            $tenantId,
            $sellerId,
        );
    }

    public function getTotal(): float|int
    {
        return array_reduce($this->dataPoints, fn ($carry, $point) => $carry + $point->value, 0);
    }

    public function getAverage(): float
    {
        if (empty($this->dataPoints)) {
            return 0.0;
        }

        return (float) ($this->getTotal() / count($this->dataPoints));
    }

    public function getMax(): float|int
    {
        return max(array_map(fn ($point) => $point->value, $this->dataPoints));
    }

    public function getMin(): float|int
    {
        return min(array_map(fn ($point) => $point->value, $this->dataPoints));
    }

    public function toArray(): array
    {
        return [
            'metric_type' => (string) $this->metricType,
            'period' => (string) $this->period,
            'period_from' => $this->period->from()->toIso8601String(),
            'period_to' => $this->period->to()->toIso8601String(),
            'group_by' => $this->groupBy,
            'data_points' => array_map(fn ($point) => $point->toArray(), $this->dataPoints),
            'tenant_id' => $this->tenantId,
            'seller_id' => $this->sellerId,
            'summary' => [
                'total' => $this->getTotal(),
                'average' => $this->getAverage(),
                'max' => $this->getMax(),
                'min' => $this->getMin(),
            ],
        ];
    }
}
