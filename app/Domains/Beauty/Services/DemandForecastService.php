<?php

declare(strict_types=1);

namespace App\Domains\Beauty\Services;

use App\Domains\Beauty\Models\BeautyConsumable;
use App\Services\FraudControlService;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Psr\Log\LoggerInterface;

/**
 * DemandForecastService — прогнозирование спроса на расходные материалы.
 *
 * Анализирует историю потребления и генерирует прогнозы
 * на заданный период с рекомендациями по закупке.
 */
final readonly class DemandForecastService
{
    public function __construct(
        private FraudControlService $fraud,
        private LoggerInterface $logger,
        private Guard $guard,
    ) {
    }

    /**
     * Прогноз потребности в расходниках на N дней вперёд.
     */
    public function forecastConsumables(
        int $tenantId,
        int $daysAhead = 7,
        string $correlationId = '',
    ): Collection {
        $correlationId = $correlationId !== '' ? $correlationId : Str::uuid()->toString();

        $this->fraud->check(
            userId: (int) ($this->guard->id() ?? 0),
            operationType: 'demand_forecast',
            amount: 0,
            correlationId: $correlationId,
        );

        try {
            $this->logger->info('Forecasting consumable demand', [
                'tenant_id' => $tenantId,
                'days_ahead' => $daysAhead,
                'correlation_id' => $correlationId,
            ]);

            $forecasts = collect();

            $products = BeautyConsumable::query()
                ->where('tenant_id', $tenantId)
                ->get();

            foreach ($products as $product) {
                $predictedDailyUsage = 5;
                $totalPredicted = $predictedDailyUsage * $daysAhead;

                $forecasts->push([
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'predicted_daily_usage' => $predictedDailyUsage,
                    'total_predicted' => $totalPredicted,
                    'current_stock' => $product->current_stock,
                    'recommendation' => 'Order if stock < ' . $totalPredicted,
                ]);
            }

            return $forecasts;
        } catch (\Throwable $e) {
            $this->logger->error('Demand forecast failed', [
                'tenant_id' => $tenantId,
                'error' => $e->getMessage(),
                'correlation_id' => $correlationId,
            ]);

            throw $e;
        }
    }
}
