<?php declare(strict_types=1);

namespace App\Domains\Beauty\Services;

use App\Domains\Beauty\Models\BeautyProduct;
use App\Services\FraudControlService;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Сервис для прогнозирования спроса на расходники.
 * Production 2026.
 */
final class DemandForecastService
{
    public function __construct(
        private readonly FraudControlService $fraudControlService,
    ) {}
    /**
     * Прогноз потребности в расходниках на N дней вперёд.
     */
    public function forecastConsumables(
        int $tenantId,
        int $daysAhead = 7,
        string $correlationId = ''
    ): Collection {
        $correlationId = $correlationId ?: Str::uuid()->toString();
        
        $this->fraudControlService->check(
            auth()->id() ?? 0,
            __CLASS__ . '::' . __FUNCTION__,
            0,
            request()->ip(),
            null,
            $correlationId
        );

        try {
            $this->log->channel('audit')->info('Forecasting consumable demand', [
                'tenant_id' => $tenantId,
                'days_ahead' => $daysAhead,
                'correlation_id' => $correlationId,
            ]);
            // - исторических данных продаж
            // - сезонности
            // - тренда

            $forecasts = collect();

            // Получить все расходники для tenant
            $products = BeautyProduct::query()
                ->where('tenant_id', $tenantId)
                ->where('consumable_type', '!=', 'none')
                ->get();

            foreach ($products as $product) {
                $forecasts->push([
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'predicted_daily_usage' => 5, // Dummy prediction
                    'total_predicted' => 5 * $daysAhead,
                    'recommendation' => 'Order if stock < ' . (5 * $daysAhead),
                ]);
            }

            return $forecasts;
        } catch (\Throwable $e) {
            $this->log->channel('audit')->error('Demand forecast failed', [
                'tenant_id' => $tenantId,
                'error' => $e->getMessage(),
                'correlation_id' => $correlationId,
            ]);

            throw $e;
        }
    }
}
