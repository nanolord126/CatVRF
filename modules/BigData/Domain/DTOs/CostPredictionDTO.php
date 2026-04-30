<?php

declare(strict_types=1);

namespace Modules\BigData\Domain\DTOs;

/**
 * Cost Prediction DTO
 *
 * Immutable DTO for monthly cost forecast.
 */
final readonly class CostPredictionDTO
{
    public function __construct(
        public string $month,
        public float $currentSpendUsd,
        public float $predictedTotalUsd,
        public float $monthlyBudgetUsd,
        public float $budgetUtilizationPredicted,
        public float $dailyAverageUsd,
        public int $daysRemaining,
        public float $trendPercent,  // positive = costs increasing
        /** @var array<string, float> service => predicted cost */
        public array $byServicePrediction,
    ) {}

    public function isOnTrack(): bool
    {
        return $this->budgetUtilizationPredicted <= 1.0;
    }

    public function projectedOverageUsd(): float
    {
        return max(0, $this->predictedTotalUsd - $this->monthlyBudgetUsd);
    }

    public function toArray(): array
    {
        return [
            'month' => $this->month,
            'current_spend_usd' => round($this->currentSpendUsd, 2),
            'predicted_total_usd' => round($this->predictedTotalUsd, 2),
            'monthly_budget_usd' => round($this->monthlyBudgetUsd, 2),
            'budget_utilization_predicted' => round($this->budgetUtilizationPredicted * 100, 1),
            'daily_average_usd' => round($this->dailyAverageUsd, 2),
            'days_remaining' => $this->daysRemaining,
            'trend_percent' => round($this->trendPercent * 100, 1),
            'on_track' => $this->isOnTrack(),
            'projected_overage_usd' => round($this->projectedOverageUsd(), 2),
            'by_service_prediction' => array_map(fn(float $v) => round($v, 2), $this->byServicePrediction),
        ];
    }
}
