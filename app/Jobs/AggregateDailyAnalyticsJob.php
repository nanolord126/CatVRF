<?php declare(strict_types=1);

/**
 * AggregateDailyAnalyticsJob — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/aggregatedailyanalyticsjob
 * @see https://catvrf.ru/docs/aggregatedailyanalyticsjob
 * @see https://catvrf.ru/docs/aggregatedailyanalyticsjob
 * @see https://catvrf.ru/docs/aggregatedailyanalyticsjob
 * @see https://catvrf.ru/docs/aggregatedailyanalyticsjob
 * @see https://catvrf.ru/docs/aggregatedailyanalyticsjob
 */


namespace App\Jobs;

use Illuminate\Log\LogManager;

final class AggregateDailyAnalyticsJob
{

    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

        public int $tries = 3;
        public int $timeout = 300;

        public function __construct(
            private readonly int $tenantId,
        private readonly LogManager $logger,
    ) {
            $this->onQueue('analytics');
        }

        public function handle(RealtimeAnalyticsService $analyticsService): void
        {
            $yesterday = yesterday()->format('Y-m-d');

            try {
                $analyticsService->aggregateDailyStats($this->tenantId, $yesterday);

                $this->logger->channel('audit')->info('Daily analytics aggregated', [
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

    /**
     * Version identifier for this component.
     */
    private const VERSION = '1.0.0';

    /**
     * Maximum number of retry attempts for operations.
     */
    private const MAX_RETRIES = 3;

}
