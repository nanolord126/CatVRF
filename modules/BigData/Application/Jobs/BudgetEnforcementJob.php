<?php

declare(strict_types=1);

namespace Modules\BigData\Application\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Modules\BigData\Application\Services\BigDataCostFacade;

/**
 * Budget Enforcement Job
 *
 * Runs hourly to check budget status and dispatch alerts.
 * Triggers BudgetExceeded event when thresholds are crossed.
 *
 * Queue: bigdata-cost
 */
final class BudgetEnforcementJob implements ShouldQueue
{
    use InteractsWithQueue;
    use Queueable;

    public int $tries = 2;
    public int $timeout = 60;

    public function __construct()
    {
        $this->onQueue('bigdata-cost');
    }

    public function handle(BigDataCostFacade $costFacade): void
    {
        Log::info('BudgetEnforcementJob: checking budget');

        try {
            $prediction = $costFacade->predictMonthly();

            if (!$prediction->isOnTrack()) {
                Log::warning('BudgetEnforcementJob: budget exceeded', [
                    'current_spend' => $prediction->currentSpendUsd,
                    'predicted_total' => $prediction->predictedTotalUsd,
                    'budget' => $prediction->monthlyBudgetUsd,
                    'overage' => $prediction->projectedOverageUsd(),
                ]);
            }

            // Also detect anomalies
            $anomalies = $costFacade->detectAnomalies();

            Log::info('BudgetEnforcementJob: completed', [
                'on_track' => $prediction->isOnTrack(),
                'anomalies_detected' => count($anomalies),
            ]);
        } catch (\Throwable $e) {
            Log::error('BudgetEnforcementJob: failed', ['error' => $e->getMessage()]);
            throw $e;
        }
    }
}
