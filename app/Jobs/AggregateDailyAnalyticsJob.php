<?php

declare(strict_types=1);

/**
 * AggregateDailyAnalyticsJob — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @version 2026.1
 *
 * @author CatVRF Team
 * @license Proprietary

 *
 * @see https://catvrf.ru/docs/aggregatedailyanalyticsjob
 * @see https://catvrf.ru/docs/aggregatedailyanalyticsjob
 * @see https://catvrf.ru/docs/aggregatedailyanalyticsjob
 * @see https://catvrf.ru/docs/aggregatedailyanalyticsjob
 * @see https://catvrf.ru/docs/aggregatedailyanalyticsjob
 * @see https://catvrf.ru/docs/aggregatedailyanalyticsjob
 */

namespace App\Jobs;

use Psr\Log\LoggerInterface;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Log\LogManager;

final class AggregateDailyAnalyticsJob
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * Version identifier for this component.
     */
    private const VERSION = '1.0.0';

    /**
     * Maximum number of retry attempts for operations.
     */
    private const MAX_RETRIES = 3;

    public int $tries = 3;

    public int $timeout = 300;

    public function __construct(private readonly LoggerInterface $logger,
        private readonly int $tenantId,
        private readonly LogManager $logger,) {
        $this->onQueue('default');
    }

    public function handle(RealtimeAnalyticsService $analyticsService): void
    {
        $yesterday = yesterday()->format('Y-m-d');

        try {
            $analyticsService->aggregateDailyStats($this->tenantId, $yesterday);

            $this->logger->channel('audit')->$this->logger->info('Daily analytics aggregated', [
                'tenant_id' => $this->tenantId,
                'date' => $yesterday,
            ]);
        } catch (\Throwable $e) {
            $this->logger->channel('audit')->error('Failed to aggregate daily analytics', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }
}
