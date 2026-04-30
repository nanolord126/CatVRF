<?php

declare(strict_types=1);

namespace Modules\BigData\Domain\DTOs;

use Carbon\CarbonImmutable;

/**
 * Daily Cost DTO
 *
 * Immutable DTO for daily cost data transfer between layers.
 */
final readonly class DailyCostDTO
{
    public function __construct(
        public CarbonImmutable $date,
        public float $totalCostUsd,
        public float $computeCostUsd,
        public float $storageCostUsd,
        public float $networkCostUsd,
        public float $licenseCostUsd,
        public float $otherCostUsd,
        public float $optimizableCostUsd,
        public float $optimizationPotentialUsd,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            date: CarbonImmutable::parse($data['cost_date']),
            totalCostUsd: (float) ($data['total_cost_usd'] ?? 0),
            computeCostUsd: (float) ($data['compute_cost_usd'] ?? 0),
            storageCostUsd: (float) ($data['storage_cost_usd'] ?? 0),
            networkCostUsd: (float) ($data['network_cost_usd'] ?? 0),
            licenseCostUsd: (float) ($data['license_cost_usd'] ?? 0),
            otherCostUsd: (float) ($data['other_cost_usd'] ?? 0),
            optimizableCostUsd: (float) ($data['total_optimizable_cost'] ?? 0),
            optimizationPotentialUsd: (float) ($data['total_optimization_potential'] ?? 0),
        );
    }

    public function toArray(): array
    {
        return [
            'date' => $this->date->toDateString(),
            'total_cost_usd' => round($this->totalCostUsd, 4),
            'compute_cost_usd' => round($this->computeCostUsd, 4),
            'storage_cost_usd' => round($this->storageCostUsd, 4),
            'network_cost_usd' => round($this->networkCostUsd, 4),
            'license_cost_usd' => round($this->licenseCostUsd, 4),
            'other_cost_usd' => round($this->otherCostUsd, 4),
            'optimizable_cost_usd' => round($this->optimizableCostUsd, 4),
            'optimization_potential_usd' => round($this->optimizationPotentialUsd, 4),
        ];
    }
}
