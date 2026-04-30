<?php

declare(strict_types=1);

namespace Modules\BigData\Domain\ValueObjects;

use Modules\BigData\Domain\Enums\BudgetStatus;

/**
 * BudgetThreshold Value Object
 *
 * Encapsulates budget thresholds for cost alerting.
 * Immutable, validates invariants on creation.
 */
final readonly class BudgetThreshold
{
    public function __construct(
        public float $monthlyBudgetUsd,
        public float $warningPercent = 0.80,
        public float $criticalPercent = 1.00,
        public float $emergencyPercent = 1.20,
        public float $gmvMaxRatio = 0.05,
    ) {
        if ($this->monthlyBudgetUsd < 0) {
            throw new \DomainException('Monthly budget cannot be negative');
        }
        if ($this->warningPercent >= $this->criticalPercent) {
            throw new \DomainException('Warning threshold must be below critical');
        }
        if ($this->criticalPercent >= $this->emergencyPercent) {
            throw new \DomainException('Critical threshold must be below emergency');
        }
    }

    public static function fromConfig(): self
    {
        return new self(
            monthlyBudgetUsd: (float) config('bigdata.cost.monthly_budget_usd', 5000),
            warningPercent: (float) config('bigdata.cost.warning_threshold', 0.80),
            criticalPercent: (float) config('bigdata.cost.critical_threshold', 1.00),
            emergencyPercent: (float) config('bigdata.cost.emergency_threshold', 1.20),
            gmvMaxRatio: (float) config('bigdata.cost.gmv_max_ratio', 0.05),
        );
    }

    public function evaluate(float $currentSpendUsd): BudgetStatus
    {
        $utilization = $this->monthlyBudgetUsd > 0
            ? $currentSpendUsd / $this->monthlyBudgetUsd
            : 0;

        return BudgetStatus::fromUtilization($utilization);
    }

    public function warningThresholdUsd(): float
    {
        return $this->monthlyBudgetUsd * $this->warningPercent;
    }

    public function criticalThresholdUsd(): float
    {
        return $this->monthlyBudgetUsd * $this->criticalPercent;
    }

    public function emergencyThresholdUsd(): float
    {
        return $this->monthlyBudgetUsd * $this->emergencyPercent;
    }

    public function isWithinBudget(float $spendUsd): bool
    {
        return $spendUsd <= $this->monthlyBudgetUsd;
    }

    public function isWithinGmvTarget(float $spendUsd, float $gmv): bool
    {
        return $gmv > 0 && ($spendUsd / $gmv) <= $this->gmvMaxRatio;
    }

    public function toArray(): array
    {
        return [
            'monthly_budget_usd' => $this->monthlyBudgetUsd,
            'warning_threshold_usd' => $this->warningThresholdUsd(),
            'critical_threshold_usd' => $this->criticalThresholdUsd(),
            'emergency_threshold_usd' => $this->emergencyThresholdUsd(),
            'gmv_max_ratio' => $this->gmvMaxRatio,
        ];
    }
}
