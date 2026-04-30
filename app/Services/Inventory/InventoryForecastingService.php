<?php

declare(strict_types=1);

namespace App\Services\Inventory;

use App\Services\Security\AuditService;
use App\Traits\WithAuditLogging;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Facades\Cache;
use Psr\Log\LoggerInterface;

/**
 * Inventory Forecasting Service
 *
 * Provides demand forecasting for inventory planning:
 * - Simple moving average
 * - Weighted moving average
 * - Exponential smoothing
 * - Seasonal adjustments
 *
 * @author CatVRF Team
 * @version 2026.04.28
 */
final readonly class InventoryForecastingService
{
    use WithAuditLogging;

    public function __construct(
        private readonly DatabaseManager $db,
        private readonly LoggerInterface $logger,
        private readonly AuditService $auditService,
    ) {}

    /**
     * Forecast demand using simple moving average
     *
     * @param  int  $inventoryItemId  Inventory item ID
     * @param  int  $period  Period for moving average (default: 30 days)
     * @param  int  $forecastDays  Number of days to forecast (default: 30)
     * @return array Forecast data
     */
    public function forecastSMA(
        int $inventoryItemId,
        int $period = 30,
        int $forecastDays = 30
    ): array {
        $cacheKey = "inventory_forecast:sma:{$inventoryItemId}:{$period}:{$forecastDays}";

        return Cache::remember($cacheKey, 3600, function () use (
            $inventoryItemId,
            $period,
            $forecastDays
        ) {
            $historicalData = $this->getHistoricalDemand($inventoryItemId, $period * 2);

            if (count($historicalData) < $period) {
                return [
                    'inventory_item_id' => $inventoryItemId,
                    'method' => 'simple_moving_average',
                    'error' => 'Insufficient historical data',
                ];
            }

            $sma = $this->calculateSimpleMovingAverage($historicalData, $period);
            $forecast = [];

            for ($day = 1; $day <= $forecastDays; $day++) {
                $forecast[] = [
                    'day' => $day,
                    'date' => now()->addDays($day)->toDateString(),
                    'forecasted_demand' => round($sma, 2),
                ];
            }

            return [
                'inventory_item_id' => $inventoryItemId,
                'method' => 'simple_moving_average',
                'period' => $period,
                'sma' => round($sma, 2),
                'forecast_days' => $forecastDays,
                'forecast' => $forecast,
                'generated_at' => now()->toIso8601String(),
            ];
        });
    }

    /**
     * Forecast demand using weighted moving average
     *
     * @param  int  $inventoryItemId  Inventory item ID
     * @param  int  $period  Period for moving average (default: 30 days)
     * @param  int  $forecastDays  Number of days to forecast (default: 30)
     * @param  array|null  $weights  Custom weights (optional)
     * @return array Forecast data
     */
    public function forecastWMA(
        int $inventoryItemId,
        int $period = 30,
        int $forecastDays = 30,
        ?array $weights = null
    ): array {
        $cacheKey = "inventory_forecast:wma:{$inventoryItemId}:{$period}:{$forecastDays}";

        return Cache::remember($cacheKey, 3600, function () use (
            $inventoryItemId,
            $period,
            $forecastDays,
            $weights
        ) {
            $historicalData = $this->getHistoricalDemand($inventoryItemId, $period * 2);

            if (count($historicalData) < $period) {
                return [
                    'inventory_item_id' => $inventoryItemId,
                    'method' => 'weighted_moving_average',
                    'error' => 'Insufficient historical data',
                ];
            }

            $wma = $this->calculateWeightedMovingAverage($historicalData, $period, $weights);
            $forecast = [];

            for ($day = 1; $day <= $forecastDays; $day++) {
                $forecast[] = [
                    'day' => $day,
                    'date' => now()->addDays($day)->toDateString(),
                    'forecasted_demand' => round($wma, 2),
                ];
            }

            return [
                'inventory_item_id' => $inventoryItemId,
                'method' => 'weighted_moving_average',
                'period' => $period,
                'wma' => round($wma, 2),
                'forecast_days' => $forecastDays,
                'forecast' => $forecast,
                'generated_at' => now()->toIso8601String(),
            ];
        });
    }

    /**
     * Forecast demand using exponential smoothing
     *
     * @param  int  $inventoryItemId  Inventory item ID
     * @param  float  $alpha  Smoothing factor (0.0-1.0, default: 0.3)
     * @param  int  $forecastDays  Number of days to forecast (default: 30)
     * @return array Forecast data
     */
    public function forecastExponentialSmoothing(
        int $inventoryItemId,
        float $alpha = 0.3,
        int $forecastDays = 30
    ): array {
        $cacheKey = "inventory_forecast:exp_smooth:{$inventoryItemId}:{$alpha}:{$forecastDays}";

        return Cache::remember($cacheKey, 3600, function () use (
            $inventoryItemId,
            $alpha,
            $forecastDays
        ) {
            $historicalData = $this->getHistoricalDemand($inventoryItemId, 90);

            if (count($historicalData) < 10) {
                return [
                    'inventory_item_id' => $inventoryItemId,
                    'method' => 'exponential_smoothing',
                    'error' => 'Insufficient historical data',
                ];
            }

            $smoothedValue = $this->calculateExponentialSmoothing($historicalData, $alpha);
            $forecast = [];

            for ($day = 1; $day <= $forecastDays; $day++) {
                $forecast[] = [
                    'day' => $day,
                    'date' => now()->addDays($day)->toDateString(),
                    'forecasted_demand' => round($smoothedValue, 2),
                ];
            }

            return [
                'inventory_item_id' => $inventoryItemId,
                'method' => 'exponential_smoothing',
                'alpha' => $alpha,
                'smoothed_value' => round($smoothedValue, 2),
                'forecast_days' => $forecastDays,
                'forecast' => $forecast,
                'generated_at' => now()->toIso8601String(),
            ];
        });
    }

    /**
     * Forecast demand with seasonal adjustment
     *
     * @param  int  $inventoryItemId  Inventory item ID
     * @param  int  $forecastDays  Number of days to forecast (default: 30)
     * @return array Forecast data
     */
    public function forecastSeasonal(int $inventoryItemId, int $forecastDays = 30): array
    {
        $cacheKey = "inventory_forecast:seasonal:{$inventoryItemId}:{$forecastDays}";

        return Cache::remember($cacheKey, 3600, function () use (
            $inventoryItemId,
            $forecastDays
        ) {
            $historicalData = $this->getHistoricalDemand($inventoryItemId, 365);

            if (count($historicalData) < 90) {
                return [
                    'inventory_item_id' => $inventoryItemId,
                    'method' => 'seasonal',
                    'error' => 'Insufficient historical data for seasonal analysis',
                ];
            }

            $baseDemand = array_sum(array_slice($historicalData, -30)) / 30;
            $seasonalFactors = $this->calculateSeasonalFactors($historicalData);

            $forecast = [];

            for ($day = 1; $day <= $forecastDays; $day++) {
                $forecastDate = now()->addDays($day);
                $dayOfYear = $forecastDate->dayOfYear;
                $seasonalFactor = $seasonalFactors[$dayOfYear] ?? 1.0;

                $forecast[] = [
                    'day' => $day,
                    'date' => $forecastDate->toDateString(),
                    'base_demand' => round($baseDemand, 2),
                    'seasonal_factor' => round($seasonalFactor, 2),
                    'forecasted_demand' => round($baseDemand * $seasonalFactor, 2),
                ];
            }

            return [
                'inventory_item_id' => $inventoryItemId,
                'method' => 'seasonal',
                'base_demand' => round($baseDemand, 2),
                'forecast_days' => $forecastDays,
                'forecast' => $forecast,
                'generated_at' => now()->toIso8601String(),
            ];
        });
    }

    /**
     * Get forecast accuracy metrics
     *
     * @param  int  $inventoryItemId  Inventory item ID
     * @param  string  $method  Forecast method
     * @param  int  $testDays  Number of days to test (default: 30)
     * @return array Accuracy metrics
     */
    public function getForecastAccuracy(
        int $inventoryItemId,
        string $method = 'sma',
        int $testDays = 30
    ): array {
        $historicalData = $this->getHistoricalDemand($inventoryItemId, $testDays * 2);

        if (count($historicalData) < $testDays * 2) {
            return [
                'inventory_item_id' => $inventoryItemId,
                'method' => $method,
                'error' => 'Insufficient historical data',
            ];
        }

        $trainingData = array_slice($historicalData, 0, -$testDays);
        $actualData = array_slice($historicalData, -$testDays);

        $forecastedData = [];

        foreach ($actualData as $index => $actual) {
            $trainingSubset = array_merge($trainingData, array_slice($actualData, 0, $index));

            $forecast = match ($method) {
                'sma' => $this->calculateSimpleMovingAverage($trainingSubset, min(30, count($trainingSubset))),
                'wma' => $this->calculateWeightedMovingAverage($trainingSubset, min(30, count($trainingSubset))),
                'exp_smooth' => $this->calculateExponentialSmoothing($trainingSubset, 0.3),
                default => $this->calculateSimpleMovingAverage($trainingSubset, min(30, count($trainingSubset))),
            };

            $forecastedData[] = $forecast;
        }

        $mae = $this->calculateMAE($actualData, $forecastedData);
        $mape = $this->calculateMAPE($actualData, $forecastedData);
        $rmse = $this->calculateRMSE($actualData, $forecastedData);

        return [
            'inventory_item_id' => $inventoryItemId,
            'method' => $method,
            'test_days' => $testDays,
            'mae' => round($mae, 2),
            'mape' => round($mape, 2),
            'rmse' => round($rmse, 2),
            'accuracy_percentage' => round(100 - $mape, 2),
            'calculated_at' => now()->toIso8601String(),
        ];
    }

    /**
     * Get historical demand data
     *
     * @param  int  $inventoryItemId  Inventory item ID
     * @param  int  $days  Number of days
     * @return array Demand data
     */
    private function getHistoricalDemand(int $inventoryItemId, int $days): array
    {
        $movements = $this->db->table('stock_movements')
            ->where('inventory_item_id', $inventoryItemId)
            ->where('type', 'out')
            ->where('created_at', '>=', now()->subDays($days))
            ->selectRaw('DATE(created_at) as date, SUM(ABS(quantity)) as demand')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        $demandData = [];

        for ($i = $days - 1; $i >= 0; $i--) {
            $date = now()->subDays($i)->toDateString();
            $movement = $movements->firstWhere('date', $date);
            $demandData[] = $movement ? (float) $movement->demand : 0.0;
        }

        return $demandData;
    }

    /**
     * Calculate simple moving average
     *
     * @param  array  $data  Data
     * @param  int  $period  Period
     * @return float SMA
     */
    private function calculateSimpleMovingAverage(array $data, int $period): float
    {
        $recentData = array_slice($data, -$period);

        return count($recentData) > 0 ? array_sum($recentData) / count($recentData) : 0.0;
    }

    /**
     * Calculate weighted moving average
     *
     * @param  array  $data  Data
     * @param  int  $period  Period
     * @param  array|null  $weights  Custom weights
     * @return float WMA
     */
    private function calculateWeightedMovingAverage(
        array $data,
        int $period,
        ?array $weights = null
    ): float {
        $recentData = array_slice($data, -$period);

        if (empty($recentData)) {
            return 0.0;
        }

        if ($weights === null) {
            $weights = range(1, count($recentData));
        }

        if (count($weights) !== count($recentData)) {
            $weights = range(1, count($recentData));
        }

        $weightedSum = 0;
        $totalWeight = array_sum($weights);

        foreach ($recentData as $index => $value) {
            $weightedSum += $value * $weights[$index];
        }

        return $totalWeight > 0 ? $weightedSum / $totalWeight : 0.0;
    }

    /**
     * Calculate exponential smoothing
     *
     * @param  array  $data  Data
     * @param  float  $alpha  Smoothing factor
     * @return float Smoothed value
     */
    private function calculateExponentialSmoothing(array $data, float $alpha): float
    {
        if (empty($data)) {
            return 0.0;
        }

        $smoothed = $data[0];

        for ($i = 1; $i < count($data); $i++) {
            $smoothed = $alpha * $data[$i] + (1 - $alpha) * $smoothed;
        }

        return $smoothed;
    }

    /**
     * Calculate seasonal factors
     *
     * @param  array  $data  Data
     * @return array Seasonal factors by day of year
     */
    private function calculateSeasonalFactors(array $data): array
    {
        $dayOfWeekFactors = [];
        $counts = [];

        $startDate = now()->subDays(count($data) - 1);

        foreach ($data as $index => $value) {
            $date = $startDate->copy()->addDays($index);
            $dayOfWeek = $date->dayOfWeek;

            if (! isset($dayOfWeekFactors[$dayOfWeek])) {
                $dayOfWeekFactors[$dayOfWeek] = 0;
                $counts[$dayOfWeek] = 0;
            }

            $dayOfWeekFactors[$dayOfWeek] += $value;
            $counts[$dayOfWeek]++;
        }

        $averageDemand = array_sum($data) / count($data);

        foreach ($dayOfWeekFactors as $day => $sum) {
            $dayOfWeekFactors[$day] = $counts[$day] > 0 ? ($sum / $counts[$day]) / $averageDemand : 1.0;
        }

        $seasonalFactors = [];

        for ($dayOfYear = 1; $dayOfYear <= 366; $dayOfYear++) {
            $date = now()->setDayOfYear($dayOfYear);
            $seasonalFactors[$dayOfYear] = $dayOfWeekFactors[$date->dayOfWeek] ?? 1.0;
        }

        return $seasonalFactors;
    }

    /**
     * Calculate Mean Absolute Error
     *
     * @param  array  $actual  Actual values
     * @param  array  $forecasted  Forecasted values
     * @return float MAE
     */
    private function calculateMAE(array $actual, array $forecasted): float
    {
        $errors = [];

        foreach ($actual as $index => $value) {
            $errors[] = abs($value - ($forecasted[$index] ?? 0));
        }

        return count($errors) > 0 ? array_sum($errors) / count($errors) : 0.0;
    }

    /**
     * Calculate Mean Absolute Percentage Error
     *
     * @param  array  $actual  Actual values
     * @param  array  $forecasted  Forecasted values
     * @return float MAPE
     */
    private function calculateMAPE(array $actual, array $forecasted): float
    {
        $errors = [];

        foreach ($actual as $index => $value) {
            if ($value != 0) {
                $errors[] = abs(($value - ($forecasted[$index] ?? 0)) / $value) * 100;
            }
        }

        return count($errors) > 0 ? array_sum($errors) / count($errors) : 0.0;
    }

    /**
     * Calculate Root Mean Square Error
     *
     * @param  array  $actual  Actual values
     * @param  array  $forecasted  Forecasted values
     * @return float RMSE
     */
    private function calculateRMSE(array $actual, array $forecasted): float
    {
        $errors = [];

        foreach ($actual as $index => $value) {
            $errors[] = pow($value - ($forecasted[$index] ?? 0), 2);
        }

        $mse = count($errors) > 0 ? array_sum($errors) / count($errors) : 0.0;

        return sqrt($mse);
    }
}
