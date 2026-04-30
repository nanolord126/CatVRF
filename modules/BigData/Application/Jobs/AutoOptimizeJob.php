<?php

declare(strict_types=1);

namespace Modules\BigData\Application\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Modules\BigData\Application\Services\BigDataCostFacade;

/**
 * Auto-Optimize Job
 *
 * Runs daily to analyze costs and auto-apply safe optimizations.
 * Only applies low-risk, high-confidence recommendations.
 *
 * Queue: bigdata-cost
 */
final class AutoOptimizeJob implements ShouldQueue
{
    use InteractsWithQueue;
    use Queueable;

    public int $tries = 1;
    public int $timeout = 120;

    public function __construct()
    {
        $this->onQueue('bigdata-cost');
    }

    public function handle(BigDataCostFacade $costFacade): void
    {
        Log::info('AutoOptimizeJob: starting analysis');

        try {
            // 1. Generate recommendations
            $recommendations = $costFacade->optimizeRecommendations();

            Log::info('AutoOptimizeJob: recommendations generated', [
                'count' => count($recommendations),
            ]);

            // 2. Auto-apply safe ones
            $results = $costFacade->autoOptimize();

            $applied = count(array_filter($results));
            $skipped = count($results) - $applied;

            Log::info('AutoOptimizeJob: completed', [
                'applied' => $applied,
                'skipped' => $skipped,
                'total' => count($results),
            ]);
        } catch (\Throwable $e) {
            Log::error('AutoOptimizeJob: failed', ['error' => $e->getMessage()]);
            throw $e;
        }
    }
}
