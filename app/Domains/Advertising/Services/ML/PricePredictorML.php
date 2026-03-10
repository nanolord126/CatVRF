<?php

namespace App\Domains\Advertising\Services\ML;

use App\Domains\Advertising\Models\AdAuctionBid;
use App\Models\AuditLog;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Throwable;

/**
 * PricePredictorML - ML сервис предсказания оптимальных ставок CPM (Production 2026).
 * 
 * Использует:
 * - Исторические данные аукционов
 * - Анализ зон высокого/низкого трафика
 * - Динамическую ценовую оптимизацию
 * - Кеширование для high-load сценариев
 */
class PricePredictorML
{
    private string $correlationId;
    private int $historyLimit = 100;  // Анализируем последние N ставок

    public function __construct()
    {
        $this->correlationId = Str::uuid()->toString();
    }

    /**
     * Предсказание оптимальной ставки CPM для слота в реальном времени (Production 2026).
     * Использует исторические данные аукционов с ML анализом.
     *
     * @param int $placementId ID плейсмента (слота)
     * @param array $context Дополнительный контекст (day_of_week, hour, competition и т.д.)
     * @return array ['guaranteed' => float, 'recommended' => float, 'minimum' => float, 'confidence' => float]
     * 
     * @throws \InvalidArgumentException При невалидных параметрах
     */
    public function predictOptimalCpm(int $placementId, array $context = []): array
    {
        try {
            // === Валидация входных данных ===
            if ($placementId <= 0) {
                throw new \InvalidArgumentException("Invalid placement ID: {$placementId}");
            }

            // === Проверка кеша (5 минут) ===
            $cacheKey = "price_prediction:{$placementId}:" . md5(json_encode($context));
            $cached = Cache::get($cacheKey);
            
            if ($cached) {
                Log::debug('Price prediction from cache', [
                    'placement_id' => $placementId,
                    'cache_key' => $cacheKey,
                ]);
                return $cached;
            }

            Log::info('Starting price prediction analysis', [
                'placement_id' => $placementId,
                'has_context' => !empty($context),
                'correlation_id' => $this->correlationId,
            ]);

            // === Извлечение исторических ставок ===
            $historicalBids = AdAuctionBid::where('placement_id', $placementId)
                ->where('is_active', true)
                ->orderByDesc('created_at')
                ->limit($this->historyLimit)
                ->pluck('cpm_bid');

            // === Резервные значения при отсутствии истории ===
            if ($historicalBids->isEmpty()) {
                Log::warning('No historical bids found for placement', [
                    'placement_id' => $placementId,
                    'correlation_id' => $this->correlationId,
                ]);

                $result = [
                    'guaranteed' => 150.0,    // Базовая гарантированная ставка
                    'recommended' => 100.0,   // Средняя ставка
                    'minimum' => 50.0,        // Минимум для входа
                    'confidence' => 0.1,      // Низкая уверенность без истории
                    'note' => 'Default prices (no historical data)',
                ];

                Cache::put($cacheKey, $result, 300); // Кеш на 5 минут
                return $result;
            }

            // === ML анализ: статистические показатели ===
            $avg = $historicalBids->avg();
            $median = $this->calculateMedian($historicalBids);
            $stddev = $this->calculateStdDev($historicalBids);
            $max = $historicalBids->max();
            $min = $historicalBids->min();

            // === Контекстная корректировка ===
            $contextMultiplier = $this->calculateContextMultiplier($context);

            // === Расчет рекомендаций ===
            $guaranteed = ($max * 1.05) + 5;  // Чтобы перебить максимум с маржей
            $recommended = ($median * 1.2) * $contextMultiplier;  // На 20% выше медианы
            $minimum = max($min * 0.7, $avg * 0.5);  // 70% от минимума или 50% от среднего

            // === Расчет уверенности (confidence) ===
            $dataPoints = $historicalBids->count();
            $confidence = min(($dataPoints / $this->historyLimit), 1.0);

            // === Прогнозирование тренда (растущие/падающие цены) ===
            $recentBids = AdAuctionBid::where('placement_id', $placementId)
                ->orderByDesc('created_at')
                ->limit(10)
                ->pluck('cpm_bid');

            $trend = $this->detectTrend($recentBids);

            if ($trend === 'rising') {
                $guaranteed *= 1.1;  // +10% при растущем тренде
                $recommended *= 1.15;
            } elseif ($trend === 'falling') {
                $guaranteed *= 0.95;  // -5% при падающем тренде
                $minimum *= 0.9;
            }

            $result = [
                'guaranteed' => round($guaranteed, 2),
                'recommended' => round($recommended, 2),
                'minimum' => round($minimum, 2),
                'confidence' => round($confidence, 2),
                'statistics' => [
                    'data_points' => $dataPoints,
                    'average' => round($avg, 2),
                    'median' => round($median, 2),
                    'stddev' => round($stddev, 2),
                    'trend' => $trend,
                ],
            ];

            // === Логирование результата ===
            Log::info('Price prediction completed', [
                'placement_id' => $placementId,
                'guaranteed' => $result['guaranteed'],
                'recommended' => $result['recommended'],
                'confidence' => $result['confidence'],
                'trend' => $trend,
                'correlation_id' => $this->correlationId,
            ]);

            // === Кеширование результата ===
            Cache::put($cacheKey, $result, 300); // 5 минут

            // === Аудит логирование ===
            AuditLog::create([
                'action' => 'advertising.price_prediction',
                'description' => "Price prediction for placement {$placementId}",
                'model_type' => 'AdPlacement',
                'model_id' => $placementId,
                'correlation_id' => $this->correlationId,
                'metadata' => [
                    'recommendations' => $result,
                    'data_points_analyzed' => $dataPoints,
                ],
            ]);

            return $result;

        } catch (Throwable $e) {
            Log::error('Price prediction failed', [
                'placement_id' => $placementId,
                'error' => $e->getMessage(),
                'error_type' => get_class($e),
                'correlation_id' => $this->correlationId,
            ]);

            \Sentry\captureException($e);

            throw new \Exception("Failed to predict optimal CPM: " . $e->getMessage());
        }
    }

    /**
     * Анализ эффективности зон на хитмап (Hot/Cold zones) с ML.
     *
     * @param int $placementId ID плейсмента
     * @param string $period Период анализа (7d, 30d, 90d)
     * @return Collection Зоны с метриками эффективности
     */
    public function analyzeZoneEfficiency(int $placementId, string $period = '7d'): Collection
    {
        try {
            $daysBack = match($period) {
                '30d' => 30,
                '90d' => 90,
                default => 7,
            };

            $startDate = now()->subDays($daysBack);

            Log::debug('Zone efficiency analysis started', [
                'placement_id' => $placementId,
                'period' => $period,
                'correlation_id' => $this->correlationId,
            ]);

            $zones = DB::table('ad_interaction_logs')
                ->where('placement_id', $placementId)
                ->where('event_type', 'click')
                ->where('is_fraud_suspected', false)
                ->where('created_at', '>=', $startDate)
                ->select(
                    DB::raw('ROUND(point_x / 100) * 100 as zone_x'),
                    DB::raw('ROUND(point_y / 100) * 100 as zone_y'),
                    DB::raw('COUNT(*) as click_count'),
                    DB::raw('AVG(fraud_score) as avg_fraud_score'),
                    DB::raw('SUM(CASE WHEN event_type = "click" THEN 1 ELSE 0 END) as clicks'),
                )
                ->groupBy('zone_x', 'zone_y')
                ->having('click_count', '>', 0)
                ->orderByDesc('click_count')
                ->get();

            Log::info('Zone efficiency analysis completed', [
                'placement_id' => $placementId,
                'zones_found' => $zones->count(),
                'correlation_id' => $this->correlationId,
            ]);

            return $zones;

        } catch (Throwable $e) {
            Log::error('Zone efficiency analysis failed', [
                'placement_id' => $placementId,
                'error' => $e->getMessage(),
                'correlation_id' => $this->correlationId,
            ]);

            return collect();
        }
    }

    /**
     * Вспомогательные методы расчета статистики.
     */
    private function calculateMedian(Collection $values): float
    {
        $sorted = $values->sort()->values();
        $count = $sorted->count();
        
        if ($count % 2 === 0) {
            return ($sorted[$count / 2 - 1] + $sorted[$count / 2]) / 2;
        }
        
        return $sorted[floor($count / 2)];
    }

    private function calculateStdDev(Collection $values): float
    {
        $avg = $values->avg();
        $variance = $values->map(fn($x) => pow($x - $avg, 2))->avg();
        return sqrt($variance);
    }

    private function calculateContextMultiplier(array $context): float
    {
        $multiplier = 1.0;

        // Время суток: вечер/выходные - больше трафика
        if (isset($context['hour'])) {
            if ($context['hour'] >= 18 || $context['hour'] <= 9) {
                $multiplier *= 1.15;
            }
        }

        // День недели: выходные - больше трафика
        if (isset($context['day_of_week']) && in_array($context['day_of_week'], [0, 6])) {
            $multiplier *= 1.10;
        }

        // Сезонность
        if (isset($context['month']) && in_array($context['month'], [11, 12])) {
            $multiplier *= 1.20; // Праздничный сезон
        }

        return $multiplier;
    }

    private function detectTrend(Collection $recentBids): string
    {
        if ($recentBids->count() < 3) {
            return 'stable';
        }

        $firstHalf = $recentBids->take(5)->avg();
        $secondHalf = $recentBids->skip(5)->avg();

        $change = (($secondHalf - $firstHalf) / $firstHalf) * 100;

        if ($change > 5) {
            return 'rising';
        } elseif ($change < -5) {
            return 'falling';
        }

        return 'stable';
    }
}
