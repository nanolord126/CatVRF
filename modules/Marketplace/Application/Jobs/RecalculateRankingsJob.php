<?php

declare(strict_types=1);

namespace Modules\Marketplace\Application\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Modules\Marketplace\Application\Services\RankingEngineService;
use Psr\Log\LoggerInterface;

/**
 * Job для пересчета рейтингов позиций
 * Запускается по расписанию или вручную
 */
final class RecalculateRankingsJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;
    public int $timeout = 600;

    public function __construct(
        public readonly ?int $limit = null,
        public readonly bool $forceAll = false,
    ) {}

    public function handle(
        RankingEngineService $rankingEngine,
        LoggerInterface $logger,
    ): void {
        $logger->info('Starting ranking recalculation job', [
            'limit' => $this->limit,
            'force_all' => $this->forceAll,
        ]);

        try {
            if ($this->forceAll) {
                $rankings = $rankingEngine->recalculateAll($this->limit);
            } else {
                $count = $rankingEngine->recalculateExpired();
                $rankings = ['recalculated_count' => $count];
            }

            $logger->info('Ranking recalculation job completed', [
                'result' => $rankings,
            ]);
        } catch (\Throwable $e) {
            $logger->error('Ranking recalculation job failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    public function failed(\Throwable $exception): void
    {
        logger()->error('Ranking recalculation job failed permanently', [
            'error' => $exception->getMessage(),
            'limit' => $this->limit,
            'force_all' => $this->forceAll,
        ]);
    }
}
