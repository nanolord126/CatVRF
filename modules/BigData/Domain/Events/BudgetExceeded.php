<?php

declare(strict_types=1);

namespace Modules\BigData\Domain\Events;

use Modules\BigData\Domain\Enums\BudgetStatus;

/**
 * Budget Exceeded Event
 *
 * Dispatched when cost exceeds budget threshold.
 * Triggers alerts to Telegram/Slack/PagerDuty.
 */
final readonly class BudgetExceeded
{
    public function __construct(
        public float $currentSpendUsd,
        public float $monthlyBudgetUsd,
        public BudgetStatus $budgetStatus,
        public float $utilizationPercent,
        public float $projectedOverageUsd,
        public string $month,
    ) {}

    public function toNotification(): array
    {
        return [
            'event' => 'budget_exceeded',
            'severity' => $this->budgetStatus->toAlertSeverity()->value,
            'current_spend_usd' => round($this->currentSpendUsd, 2),
            'monthly_budget_usd' => round($this->monthlyBudgetUsd, 2),
            'utilization_percent' => round($this->utilizationPercent, 1),
            'projected_overage_usd' => round($this->projectedOverageUsd, 2),
            'month' => $this->month,
            'message' => "BigData budget {$this->budgetStatus->value}: spent \${$this->currentSpendUsd} of \${$this->monthlyBudgetUsd} ({$this->utilizationPercent}%)",
        ];
    }
}
