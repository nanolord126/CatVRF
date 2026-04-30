<?php

declare(strict_types=1);

namespace Modules\Analytics\Application\DTOs;

/**
 * Funnel Step DTO
 *
 * Represents a single step in the conversion funnel.
 */
final readonly class FunnelStepDto
{
    public function __construct(
        public readonly string $name,
        public readonly int $count,
        public readonly ?float $conversionRate = null,
        public readonly ?float $dropOffRate = null,
    ) {}

    public static function create(
        string $name,
        int $count,
        ?float $conversionRate = null,
        ?float $dropOffRate = null,
    ): self {
        return new self(
            $name,
            $count,
            $conversionRate,
            $dropOffRate,
        );
    }

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'count' => $this->count,
            'conversion_rate' => $this->conversionRate,
            'drop_off_rate' => $this->dropOffRate,
        ];
    }
}
