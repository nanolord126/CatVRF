<?php declare(strict_types=1);

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Log\LogManager;

final class RecalculateAnalyticsJob implements ShouldQueue
{
        public int $tries = 3;
        public int $timeout = 3600;  // 1 hour

        public function __construct(
            private readonly int $tenantId,
        private readonly LogManager $logger,
    ) {
        }

        public function handle(AdvancedAnalyticsService $analyticsService): void {
            $correlationId = Str::uuid()->toString();

            try {
                $this->logger->channel('audit')->info('Analytics recalculation started', [
                    'correlation_id' => $correlationId,
                    'tenant_id' => $this->tenantId,
                ]);

                // Пересчитать KPI
                $analyticsService->calculateKPIs(
                    $this->tenantId,
                    ['correlation_id' => (string)$correlationId],
                );

                // Пересчитать прогнозы
                foreach (['revenue', 'orders', 'conversion', 'aov'] as $metricType) {
                    $analyticsService->predictFutureTrend(
                        $this->tenantId,
                        $metricType,
                        30,
                        ['correlation_id' => (string)$correlationId],
                    );
                }

                $this->logger->channel('audit')->info('Analytics recalculation completed', [
                    'correlation_id' => $correlationId,
                    'tenant_id' => $this->tenantId,
                ]);
            } catch (\Exception $e) {
                $this->logger->channel('audit')->error($e->getMessage(), [
                    'exception' => $e::class,
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'correlation_id' => $correlationId,
                ]);

                $this->logger->error('Analytics recalculation failed', [
                    'correlation_id' => $correlationId,
                    'tenant_id' => $this->tenantId,
                    'error' => $e->getMessage(),
                ]);
                throw $e;
            }
        }
}
