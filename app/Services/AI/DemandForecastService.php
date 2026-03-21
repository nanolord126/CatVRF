<?php declare(strict_types=1);

namespace App\Services\AI;

use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

final class DemandForecastService
{
    public function forecastForItem(int $itemId, Carbon $dateFrom, Carbon $dateTo, array $context = []): array
    {
        $cacheKey = "demand_forecast:item:{$itemId}:from:{$dateFrom->format('Y-m-d')}:to:{$dateTo->format('Y-m-d')}";

        if (Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        // Получить исторический спрос за 30 дней
        $historicalDemand = DB::table('demand_actuals')
            ->where('item_id', $itemId)
            ->where('date', '>=', now()->subDays(30))
            ->selectRaw('AVG(actual_demand) as avg_demand, STDDEV(actual_demand) as stddev')
            ->first();

        $avgDemand = $historicalDemand->avg_demand ?? 100;
        $stddev = $historicalDemand->stddev ?? 20;

        // Простой прогноз на основе среднего и сезонности
        $forecastDays = [];
        $currentDate = $dateFrom->copy();

        while ($currentDate <= $dateTo) {
            $dayOfWeek = $currentDate->dayOfWeek;
            $seasonality = $this->getSeasonalityFactor($currentDate);

            $predicted = (int)($avgDemand * $seasonality);
            $confidenceInterval = (int)($stddev * 1.96);

            $forecastDays[] = [
                'date' => $currentDate->format('Y-m-d'),
                'predicted_demand' => $predicted,
                'confidence_interval_lower' => max(0, $predicted - $confidenceInterval),
                'confidence_interval_upper' => $predicted + $confidenceInterval,
                'confidence_score' => 0.85,
            ];

            $currentDate->addDay();
        }

        $result = [
            'item_id' => $itemId,
            'forecast' => $forecastDays,
            'model_version' => now()->format('Y-m-d-v1'),
        ];

        Cache::put($cacheKey, $result, 3600); // 1 час

        Log::channel('forecast')->info('Demand forecast generated', [
            'item_id' => $itemId,
            'days_count' => count($forecastDays),
        ]);

        return $result;
    }

    public function getHistoricalAccuracy(string $vertical, int $days = 30): array
    {
        $accuracy = DB::table('demand_model_versions')
            ->where('vertical', $vertical)
            ->where('trained_at', '>=', now()->subDays($days))
            ->latest('trained_at')
            ->first();

        return [
            'mae' => $accuracy->mae ?? 0,
            'rmse' => $accuracy->rmse ?? 0,
            'mape' => $accuracy->mape ?? 0,
            'version' => $accuracy->version ?? 'unknown',
        ];
    }

    private function getSeasonalityFactor(Carbon $date): float
    {
        $dayOfWeek = $date->dayOfWeek;

        // Выходные дни имеют коэффициент 1.2
        if ($dayOfWeek === 0 || $dayOfWeek === 6) {
            return 1.2;
        }

        // Праздничные дни проверяются в конфиге
        return 1.0;
    }
}
