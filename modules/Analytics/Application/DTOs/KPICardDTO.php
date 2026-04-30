<?php

declare(strict_types=1);

namespace Modules\Analytics\Application\DTOs;

/**
 * KPI Card DTO
 *
 * Represents a single KPI card with current value, previous value, and growth rate.
 * Used for the top dashboard metrics row.
 */
final readonly class KPICardDTO
{
    public function __construct(
        public readonly string $key,
        public readonly string $label,
        public readonly float|int $value,
        public readonly float|int|null $previousValue,
        public readonly ?float $growthRate,
        public readonly string $format = 'number', // number, currency, percentage
        public readonly string $trend = 'neutral', // up, down, neutral
        public readonly ?string $icon = null,
    ) {}

    /**
     * Create KPI card with calculated growth rate.
     */
    public static function create(
        string $key,
        string $label,
        float|int $value,
        float|int|null $previousValue = null,
        string $format = 'number',
        ?string $icon = null,
    ): self {
        $growthRate = null;
        $trend = 'neutral';

        if ($previousValue !== null && $previousValue != 0) {
            $growthRate = (($value - $previousValue) / abs($previousValue)) * 100;
            $trend = $growthRate > 0 ? 'up' : ($growthRate < 0 ? 'down' : 'neutral');
        }

        return new self(
            $key,
            $label,
            $value,
            $previousValue,
            $growthRate,
            $format,
            $trend,
            $icon,
        );
    }

    public function toArray(): array
    {
        return [
            'key' => $this->key,
            'label' => $this->label,
            'value' => $this->value,
            'previous_value' => $this->previousValue,
            'growth_rate' => $this->growthRate,
            'format' => $this->format,
            'trend' => $this->trend,
            'icon' => $this->icon,
        ];
    }
}
