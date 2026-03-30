<?php declare(strict_types=1);

namespace App\Jobs;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class RecalculateAnalyticsJob extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    use Queueable;

        public int $tries = 3;
        public int $timeout = 3600;  // 1 hour

        public function __construct(
            public readonly int $tenantId,
        ) {
        }

        public function handle(AdvancedAnalyticsService $analyticsService): void {
            $correlationId = Str::uuid()->toString();

            try {
                Log::channel('audit')->info('Analytics recalculation started', [
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

                Log::channel('audit')->info('Analytics recalculation completed', [
                    'correlation_id' => $correlationId,
                    'tenant_id' => $this->tenantId,
                ]);
            } catch (\Exception $e) {
                Log::error('Analytics recalculation failed', [
                    'correlation_id' => $correlationId,
                    'tenant_id' => $this->tenantId,
                    'error' => $e->getMessage(),
                ]);
                throw $e;
            }
        }
}
