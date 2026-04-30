<?php

declare(strict_types=1);

namespace Modules\BigData\Domain\Entities;

use Modules\BigData\Domain\Enums\BudgetStatus;
use Modules\BigData\Domain\Enums\CloudProvider;
use Modules\BigData\Domain\Enums\CostCategory;

/**
 * CostBreakdown Entity
 *
 * Immutable domain entity representing a daily cost breakdown.
 * Factory methods enforce domain invariants.
 */
final readonly class CostBreakdown
{
    private function __construct(
        public \DateTimeImmutable $date,
        public float $totalCostUsd,
        public float $totalCostRub,
        /** @var array<string, float> category => cost_usd */
        public array $byCategory,
        /** @var array<string, float> service => cost_usd */
        public array $byService,
        /** @var array<CloudProvider, float> provider => cost_usd */
        public array $byProvider,
        public float $optimizableCostUsd,
        public float $optimizationPotentialUsd,
        public float $monthlyBudgetUsd,
        public BudgetStatus $budgetStatus,
        public float $budgetUtilizationPercent,
    ) {
        $this->validate();
    }

    public static function fromMetrics(
        \DateTimeImmutable $date,
        float $totalCostUsd,
        float $totalCostRub,
        array $byCategory,
        array $byService,
        array $byProvider,
        float $optimizableCostUsd,
        float $optimizationPotentialUsd,
        float $monthlyBudgetUsd,
    ): self {
        $utilization = $monthlyBudgetUsd > 0
            ? ($totalCostUsd / $monthlyBudgetUsd) * 100
            : 0;

        return new self(
            date: $date,
            totalCostUsd: $totalCostUsd,
            totalCostRub: $totalCostRub,
            byCategory: $byCategory,
            byService: $byService,
            byProvider: $byProvider,
            optimizableCostUsd: $optimizableCostUsd,
            optimizationPotentialUsd: $optimizationPotentialUsd,
            monthlyBudgetUsd: $monthlyBudgetUsd,
            budgetStatus: BudgetStatus::fromUtilization($utilization / 100),
            budgetUtilizationPercent: $utilization,
        );
    }

    public static function unknown(\DateTimeImmutable $date): self
    {
        return new self(
            date: $date,
            totalCostUsd: 0,
            totalCostRub: 0,
            byCategory: [],
            byService: [],
            byProvider: [],
            optimizableCostUsd: 0,
            optimizationPotentialUsd: 0,
            monthlyBudgetUsd: 0,
            budgetStatus: BudgetStatus::UnderBudget,
            budgetUtilizationPercent: 0,
        );
    }

    public function isOverBudget(): bool
    {
        return $this->budgetStatus->isOverBudget();
    }

    public function costToGmvRatio(float $gmv): float
    {
        return $gmv > 0 ? $this->totalCostUsd / $gmv : 0;
    }

    public function isWithinGmvTarget(float $gmv, float $maxRatio = 0.05): bool
    {
        return $this->costToGmvRatio($gmv) <= $maxRatio;
    }

    public function topExpensiveServices(int $limit = 5): array
    {
        $services = $this->byService;
        arsort($services);

        return array_slice($services, 0, $limit, true);
    }

    public function toArray(): array
    {
        return [
            'date' => $this->date->format('Y-m-d'),
            'total_cost_usd' => round($this->totalCostUsd, 4),
            'total_cost_rub' => round($this->totalCostRub, 2),
            'by_category' => array_map(fn(float $v) => round($v, 4), $this->byCategory),
            'by_service' => array_map(fn(float $v) => round($v, 4), $this->byService),
            'by_provider' => array_map(fn(float $v) => round($v, 4), $this->byProvider),
            'optimizable_cost_usd' => round($this->optimizableCostUsd, 4),
            'optimization_potential_usd' => round($this->optimizationPotentialUsd, 4),
            'monthly_budget_usd' => $this->monthlyBudgetUsd,
            'budget_status' => $this->budgetStatus->value,
            'budget_utilization_percent' => round($this->budgetUtilizationPercent, 2),
        ];
    }

    private function validate(): void
    {
        if ($this->totalCostUsd < 0) {
            throw new \DomainException('Total cost cannot be negative');
        }
        if ($this->monthlyBudgetUsd < 0) {
            throw new \DomainException('Monthly budget cannot be negative');
        }
    }
}
