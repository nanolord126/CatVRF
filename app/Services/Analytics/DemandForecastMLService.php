<?php

declare(strict_types=1);

namespace App\Services\Analytics;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

/**
 * Demand Forecasting ML Service
 * Прогнозирование спроса на товары/услуги с учётом сезонности и тренда
 * 
 * @package App\Services\Analytics
 * @category ML / Forecasting
 */
final class DemandForecastMLService
{
    private const CACHE_TTL = 86400; // 24 часа (долгосрочный прогноз)
    private const MIN_HISTORICAL_DAYS = 30;
    private const MAX_FORECAST_DAYS = 90;
    private const CONFIDENCE_BASE = 0.75;

    /**
     * Прогнозирует спрос на товар/услугу на N дней вперёд
     * 
     * @param int $itemId
     * @param Carbon $dateFrom
     * @param Carbon $dateTo
     * @param array $context
     * @return array {predicted_demand, confidence_interval_lower, confidence_interval_upper, confidence_score}
     */
    public function forecastForItem(
        int $itemId,
        Carbon $dateFrom,
        Carbon $dateTo,
        array $context = []
    ): array {
        $daysAhead = $dateTo->diffInDays($dateFrom);
        
        if ($daysAhead > self::MAX_FORECAST_DAYS) {
            $daysAhead = self::MAX_FORECAST_DAYS;
        }

        $cacheKey = "forecast:item:{$itemId}:days:{$daysAhead}";

        return $this->cache->remember($cacheKey, self::CACHE_TTL, function () use ($itemId, $daysAhead) {
            try {
                // Получаем историю спроса
                $history = $this->getHistoricalDemand($itemId);

                if (count($history) < self::MIN_HISTORICAL_DAYS) {
                    return $this->getDefaultForecast();
                }

                // Извлекаем компоненты тренда
                $trend = $this->calculateTrend($history);
                $seasonal = $this->calculateSeasonality($history);
                $volatility = $this->calculateVolatility($history);

                // Прогнозируем
                $forecast = $this->predictLinearTrend($history, $trend, $seasonal, $daysAhead);

                // Вычисляем доверительный интервал
                $confidence = $this->calculateConfidence(count($history), $daysAhead);

                return [
                    'predicted_demand' => max(0, (int)round($forecast['value'])),
                    'confidence_interval_lower' => max(0, (int)round($forecast['value'] - $volatility)),
                    'confidence_interval_upper' => max(0, (int)round($forecast['value'] + $volatility)),
                    'confidence_score' => $confidence,
                    'trend' => $trend,
                    'seasonality' => $seasonal,
                ];

            } catch (\Throwable $e) {
                $this->log->channel('analytics_errors')->error('Demand forecast failed', [
                    'item_id' => $itemId,
                    'error' => $e->getMessage()
                ]);
                return $this->getDefaultForecast();
            }
        });
    }

    /**
     * Массовый прогноз для каталога (используется для оптимизации складских остатков)
     * 
     * @param array $itemIds
     * @param Carbon $dateFrom
     * @param Carbon $dateTo
     * @return array
     */
    public function forecastBulk(array $itemIds, Carbon $dateFrom, Carbon $dateTo): array
    {
        $forecasts = [];

        foreach ($itemIds as $itemId) {
            $forecasts[$itemId] = $this->forecastForItem($itemId, $dateFrom, $dateTo);
        }

        return $forecasts;
    }

    /**
     * Получает историческую точность модели за период
     * 
     * @param string $vertical
     * @param int $days
     * @return array {mae, rmse, mape}
     */
    public function getHistoricalAccuracy(string $vertical, int $days = 30): array
    {
        $since = now()->subDays($days)->startOfDay();

        $actuals = $this->db->table('demand_actuals')
            ->where('vertical', $vertical)
            ->where('date', '>=', $since)
            ->get()
            ->groupBy('date')
            ->map(fn ($group) => $group->sum('actual_demand'));

        $predictions = $this->db->table('demand_forecasts')
            ->where('vertical', $vertical)
            ->where('forecast_date', '>=', $since)
            ->get()
            ->groupBy('forecast_date')
            ->map(fn ($group) => $group->first()->predicted_demand ?? 0);

        $errors = [];
        foreach ($actuals as $date => $actual) {
            $predicted = $predictions->get($date, 0);
            $errors[] = abs($actual - $predicted);
        }

        if (empty($errors)) {
            return ['mae' => 0, 'rmse' => 0, 'mape' => 0];
        }

        // Mean Absolute Error
        $mae = array_sum($errors) / count($errors);

        // Root Mean Squared Error
        $rmse = sqrt(array_sum(array_map(fn ($e) => $e ** 2, $errors)) / count($errors));

        // Mean Absolute Percentage Error
        $percentErrors = [];
        foreach ($actuals as $date => $actual) {
            if ($actual > 0) {
                $predicted = $predictions->get($date, 0);
                $percentErrors[] = abs(($actual - $predicted) / $actual);
            }
        }
        $mape = !empty($percentErrors) ? (array_sum($percentErrors) / count($percentErrors)) * 100 : 0;

        return [
            'mae' => round($mae, 2),
            'rmse' => round($rmse, 2),
            'mape' => round($mape, 2),
        ];
    }

    /**
     * Получает историю спроса за последние 90 дней
     * 
     * @param int $itemId
     * @return array {date => demand}
     */
    private function getHistoricalDemand(int $itemId): array
    {
        $since = now()->subDays(90)->startOfDay();

        $history = $this->db->table('demand_actuals')
            ->where('item_id', $itemId)
            ->where('date', '>=', $since)
            ->orderBy('date')
            ->pluck('actual_demand', 'date')
            ->toArray();

        // Если истории недостаточно, заполняем нулями
        if (count($history) < self::MIN_HISTORICAL_DAYS) {
            $allDates = [];
            for ($i = 90; $i >= 0; $i--) {
                $date = now()->subDays($i)->format('Y-m-d');
                $allDates[$date] = $history[$date] ?? 0;
            }
            return $allDates;
        }

        return $history;
    }

    /**
     * Вычисляет тренд (линейный угол изменения)
     * Положительный = рост, отрицательный = спад
     * 
     * @param array $history
     * @return float
     */
    private function calculateTrend(array $history): float
    {
        $values = array_values($history);
        $n = count($values);

        if ($n < 2) {
            return 0.0;
        }

        // Метод наименьших квадратов (простой)
        $sumX = 0;
        $sumY = 0;
        $sumXY = 0;
        $sumX2 = 0;

        for ($i = 0; $i < $n; $i++) {
            $sumX += $i;
            $sumY += $values[$i];
            $sumXY += $i * $values[$i];
            $sumX2 += $i * $i;
        }

        $slope = ($n * $sumXY - $sumX * $sumY) / ($n * $sumX2 - $sumX * $sumX);

        return $slope;
    }

    /**
     * Вычисляет сезонность (день недели, например)
     * 
     * @param array $history
     * @return array {mon, tue, wed, thu, fri, sat, sun}
     */
    private function calculateSeasonality(array $history): array
    {
        $dayOfWeekAvg = [0, 0, 0, 0, 0, 0, 0];
        $dayOfWeekCount = [0, 0, 0, 0, 0, 0, 0];

        foreach ($history as $date => $demand) {
            $dow = (int)Carbon::parse($date)->format('w');
            $dayOfWeekAvg[$dow] += $demand;
            $dayOfWeekCount[$dow]++;
        }

        for ($i = 0; $i < 7; $i++) {
            if ($dayOfWeekCount[$i] > 0) {
                $dayOfWeekAvg[$i] /= $dayOfWeekCount[$i];
            }
        }

        return [
            'mon' => $dayOfWeekAvg[1],
            'tue' => $dayOfWeekAvg[2],
            'wed' => $dayOfWeekAvg[3],
            'thu' => $dayOfWeekAvg[4],
            'fri' => $dayOfWeekAvg[5],
            'sat' => $dayOfWeekAvg[6],
            'sun' => $dayOfWeekAvg[0],
        ];
    }

    /**
     * Вычисляет волатильность (стандартное отклонение)
     * Используется для доверительных интервалов
     * 
     * @param array $history
     * @return float
     */
    private function calculateVolatility(array $history): float
    {
        $values = array_values($history);
        $mean = array_sum($values) / count($values);

        $variance = array_sum(array_map(
            fn ($x) => ($x - $mean) ** 2,
            $values
        )) / count($values);

        return sqrt($variance);
    }

    /**
     * Прогнозирует линейный тренд с учётом сезонности
     * 
     * @param array $history
     * @param float $trend
     * @param array $seasonal
     * @param int $daysAhead
     * @return array {value}
     */
    private function predictLinearTrend(array $history, float $trend, array $seasonal, int $daysAhead): array
    {
        $values = array_values($history);
        $lastValue = end($values);
        $lastDate = end(array_keys($history));

        // Прогноз = последнее значение + тренд * дни впёрёд + сезонный коэффициент
        $forecastDate = Carbon::parse($lastDate)->addDays($daysAhead);
        $dow = (int)$forecastDate->format('w');
        $dowNames = ['sun', 'mon', 'tue', 'wed', 'thu', 'fri', 'sat'];
        $seasonalFactor = $seasonal[$dowNames[$dow]] ?? 0;

        $predictedValue = $lastValue + ($trend * $daysAhead) + $seasonalFactor;

        return [
            'value' => max(0, $predictedValue),
        ];
    }

    /**
     * Вычисляет коэффициент доверия к прогнозу
     * Зависит от объёма данных и горизонта прогноза
     * 
     * @param int $historicalDays
     * @param int $forecastDays
     * @return float
     */
    private function calculateConfidence(int $historicalDays, int $forecastDays): float
    {
        // Больше данных = выше доверие
        $dataConfidence = min(1.0, $historicalDays / 365);

        // Дальше прогноз = ниже доверие (линейно)
        $horizonConfidence = max(0.3, 1.0 - ($forecastDays / self::MAX_FORECAST_DAYS) * 0.4);

        return round(self::CONFIDENCE_BASE * $dataConfidence * $horizonConfidence, 2);
    }

    /**
     * Возвращает дефолтный прогноз (когда данных недостаточно)
     * 
     * @return array
     */
    private function getDefaultForecast(): array
    {
        return [
            'predicted_demand' => 100,
            'confidence_interval_lower' => 50,
            'confidence_interval_upper' => 150,
            'confidence_score' => 0.3,
            'trend' => 0.0,
            'seasonality' => [],
        ];
    }
}
