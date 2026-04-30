<?php

declare(strict_types=1);

namespace Modules\Analytics\Application\DTOs;

use Carbon\CarbonImmutable;
use Modules\Analytics\Domain\ValueObjects\Period;

/**
 * Funnel DTO
 *
 * Represents conversion funnel data (e.g., AddToCart -> Checkout -> Paid).
 */
final readonly class FunnelDto
{
    /**
     * @param FunnelStepDto[] $steps
     */
    public function __construct(
        public readonly string $funnelName,
        public readonly Period $period,
        public readonly array $steps,
        public readonly ?int $tenantId = null,
    ) {
        if (empty($steps)) {
            throw new \InvalidArgumentException('Funnel must have at least one step');
        }
    }

    /**
     * @param FunnelStepDto[] $steps
     */
    public static function create(
        string $funnelName,
        Period $period,
        array $steps,
        ?int $tenantId = null,
    ): self {
        return new self(
            $funnelName,
            $period,
            $steps,
            $tenantId,
        );
    }

    public function getTotalConversionRate(): float
    {
        if (count($this->steps) < 2) {
            return 0.0;
        }

        $firstStep = $this->steps[0];
        $lastStep = $this->steps[count($this->steps) - 1];

        if ($firstStep->count === 0) {
            return 0.0;
        }

        return ($lastStep->count / $firstStep->count) * 100;
    }

    public function toArray(): array
    {
        return [
            'funnel_name' => $this->funnelName,
            'period' => (string) $this->period,
            'period_from' => $this->period->from()->toIso8601String(),
            'period_to' => $this->period->to()->toIso8601String(),
            'steps' => array_map(fn ($step) => $step->toArray(), $this->steps),
            'total_conversion_rate' => $this->getTotalConversionRate(),
            'tenant_id' => $this->tenantId,
        ];
    }
}

