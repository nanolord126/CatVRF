<?php

declare(strict_types=1);

namespace App\Domains\Consulting\Analytics\Services;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Сервис для сравнения двух периодов данных
 * 
 * Вычисляет: дельта %, тренд, различия по метрикам
 * Поддерживает кэширование (5m/1h/24h)
 * Correlation ID tracking для трассировки
 */
final class ComparisonHeatmapService
{
    public function __construct(
        private readonly ClickHouseService $clickHouseService,
    ) {
    }

    private string $correlationId = '';

    /**
     * Сравнить два периода по геоданным
     * 
     * @param int $tenantId
     * @param string $vertical Вертикаль (beauty, food, auto и т.д.)
     * @param Carbon $period1From Начало первого периода
     * @param Carbon $period1To Конец первого периода
     * @param Carbon $period2From Начало второго периода
     * @param Carbon $period2To Конец второго периода
     * @param string $metric Метрика (event_count, unique_users, unique_sessions)
     * @return array {comparison_type, metric, period1, period2, data[], metadata}
     */
    public function compareGeoTimeSeries(
        int $tenantId,
        string $vertical,
        Carbon $period1From,
        Carbon $period1To,
        Carbon $period2From,
        Carbon $period2To,
        string $metric = 'event_count',
    ): array {
        $cacheKey = $this->buildGeoCacheKey($tenantId, $vertical, $period1From, $period1To, $period2From, $period2To, $metric);
        
        // Проверить кэш
        $cached = Cache::get($cacheKey);
        if ($cached) {
            Log::channel('analytics')->debug('Geo comparison cache hit', [
                'correlation_id' => $this->correlationId,
                'cache_key' => $cacheKey,
                'tenant_id' => $tenantId,
            ]);
            return $cached;
        }

        try {
            // Получить данные за оба периода
            $period1Data = $this->clickHouseService->queryGeoDaily(
                $tenantId,
                $vertical,
                [$period1From, $period1To],
                $metric
            );

            $period2Data = $this->clickHouseService->queryGeoDaily(
                $tenantId,
                $vertical,
                [$period2From, $period2To],
                $metric
            );

            // Вычислить сравнение
            $comparisonResult = $this->calculateGeoComparison(
                $period1Data,
                $period2Data,
                $metric,
                $period1From,
                $period1To,
                $period2From,
                $period2To,
            );

            // Форматировать результат
            $response = [
                'comparison_type' => 'geo',
                'metric' => $metric,
                'period1' => [
                    'from' => $period1From->toIso8601String(),
                    'to' => $period1To->toIso8601String(),
                    'days' => $period1From->diffInDays($period1To),
                    'total_metric' => $comparisonResult['period1_total'],
                    'avg_daily' => $comparisonResult['period1_avg'],
                ],
                'period2' => [
                    'from' => $period2From->toIso8601String(),
                    'to' => $period2To->toIso8601String(),
                    'days' => $period2From->diffInDays($period2To),
                    'total_metric' => $comparisonResult['period2_total'],
                    'avg_daily' => $comparisonResult['period2_avg'],
                ],
                'delta' => [
                    'absolute' => $comparisonResult['absolute_delta'],
                    'percent' => $comparisonResult['percent_delta'],
                    'trend' => $comparisonResult['trend'],
                    'direction' => $comparisonResult['direction'],
                ],
                'data' => $comparisonResult['comparison_data'],
                'metadata' => [
                    'generated_at' => now()->toIso8601String(),
                    'correlation_id' => $this->correlationId,
                    'total_records' => count($comparisonResult['comparison_data']),
                    'cache_ttl_seconds' => 5 * 60, // 5 минут для дневных данных
                ],
            ];

            // Кэшировать результат (5 минут для дневных, 1 час для недельных)
            $ttl = match($period1From->diffInDays($period1To)) {
                0, 1 => 5 * 60,  // Дневные: 5 минут
                2, 3, 4, 5, 6, 7 => 3600,  // Недельные: 1 час
                default => 86400,  // Месячные: 24 часа
            };

            Cache::put($cacheKey, $response, $ttl);

            Log::channel('analytics')->info('Geo comparison generated', [
                'correlation_id' => $this->correlationId,
                'tenant_id' => $tenantId,
                'metric' => $metric,
                'period1_days' => $period1From->diffInDays($period1To),
                'period2_days' => $period2From->diffInDays($period2To),
                'delta_percent' => $comparisonResult['percent_delta'],
                'records' => count($comparisonResult['comparison_data']),
            ]);

            return $response;
        } catch (\Exception $e) {
            Log::channel('error')->error('Geo comparison failed', [
                'correlation_id' => $this->correlationId,
                'tenant_id' => $tenantId,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'comparison_type' => 'geo',
                'metric' => $metric,
                'error' => 'Не удалось загрузить данные для сравнения',
                'correlation_id' => $this->correlationId,
            ];
        }
    }

    /**
     * Сравнить два периода по клик-данным
     * 
     * @param int $tenantId
     * @param string $vertical
     * @param string $pageUrl URL страницы для фильтрации
     * @param Carbon $period1From
     * @param Carbon $period1To
     * @param Carbon $period2From
     * @param Carbon $period2To
     * @return array
     */
    public function compareClickTimeSeries(
        int $tenantId,
        string $vertical,
        string $pageUrl,
        Carbon $period1From,
        Carbon $period1To,
        Carbon $period2From,
        Carbon $period2To,
    ): array {
        $cacheKey = $this->buildClickCacheKey($tenantId, $vertical, $pageUrl, $period1From, $period1To, $period2From, $period2To);

        // Проверить кэш
        $cached = Cache::get($cacheKey);
        if ($cached) {
            Log::channel('analytics')->debug('Click comparison cache hit', [
                'correlation_id' => $this->correlationId,
                'cache_key' => $cacheKey,
            ]);
            return $cached;
        }

        try {
            // Получить данные за оба периода
            $period1Data = $this->clickHouseService->queryClickDaily(
                $tenantId,
                $vertical,
                $pageUrl,
                [$period1From, $period1To]
            );

            $period2Data = $this->clickHouseService->queryClickDaily(
                $tenantId,
                $vertical,
                $pageUrl,
                [$period2From, $period2To]
            );

            // Вычислить сравнение
            $comparisonResult = $this->calculateClickComparison(
                $period1Data,
                $period2Data,
                $period1From,
                $period1To,
                $period2From,
                $period2To,
            );

            $response = [
                'comparison_type' => 'click',
                'page_url' => $pageUrl,
                'period1' => [
                    'from' => $period1From->toIso8601String(),
                    'to' => $period1To->toIso8601String(),
                    'days' => $period1From->diffInDays($period1To),
                    'total_clicks' => $comparisonResult['period1_total'],
                    'avg_daily' => $comparisonResult['period1_avg'],
                ],
                'period2' => [
                    'from' => $period2From->toIso8601String(),
                    'to' => $period2To->toIso8601String(),
                    'days' => $period2From->diffInDays($period2To),
                    'total_clicks' => $comparisonResult['period2_total'],
                    'avg_daily' => $comparisonResult['period2_avg'],
                ],
                'delta' => [
                    'absolute' => $comparisonResult['absolute_delta'],
                    'percent' => $comparisonResult['percent_delta'],
                    'trend' => $comparisonResult['trend'],
                    'direction' => $comparisonResult['direction'],
                ],
                'heatmap_comparison' => $comparisonResult['heatmap_comparison'],
                'metadata' => [
                    'generated_at' => now()->toIso8601String(),
                    'correlation_id' => $this->correlationId,
                    'total_records' => count($comparisonResult['heatmap_comparison']),
                    'cache_ttl_seconds' => 5 * 60,
                ],
            ];

            // Кэшировать (5 минут)
            Cache::put($cacheKey, $response, 5 * 60);

            Log::channel('analytics')->info('Click comparison generated', [
                'correlation_id' => $this->correlationId,
                'tenant_id' => $tenantId,
                'page_url' => $pageUrl,
                'delta_percent' => $comparisonResult['percent_delta'],
            ]);

            return $response;
        } catch (\Exception $e) {
            Log::channel('error')->error('Click comparison failed', [
                'correlation_id' => $this->correlationId,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'comparison_type' => 'click',
                'page_url' => $pageUrl,
                'error' => 'Не удалось загрузить данные для сравнения',
                'correlation_id' => $this->correlationId,
            ];
        }
    }

    /**
     * Вычислить дельта-различия для геоданных
     */
    private function calculateGeoComparison(
        array $period1Data,
        array $period2Data,
        string $metric,
        Carbon $period1From,
        Carbon $period1To,
        Carbon $period2From,
        Carbon $period2To,
    ): array {
        // Индексировать данные по geo_hash для быстрого поиска
        $period1Map = collect($period1Data)->keyBy('geo_hash')->toArray();
        $period2Map = collect($period2Data)->keyBy('geo_hash')->toArray();

        // Получить все уникальные хэши из обоих периодов
        $allHashes = array_unique(array_merge(array_keys($period1Map), array_keys($period2Map)));

        $comparisonData = [];
        $period1Total = 0;
        $period2Total = 0;

        foreach ($allHashes as $hash) {
            $p1Value = $period1Map[$hash][$metric] ?? 0;
            $p2Value = $period2Map[$hash][$metric] ?? 0;

            $period1Total += $p1Value;
            $period2Total += $p2Value;

            // Вычислить дельту для этого хэша
            $delta = $p2Value - $p1Value;
            $deltaPercent = $p1Value > 0 ? ($delta / $p1Value) * 100 : 0;

            $comparisonData[] = [
                'geo_hash' => $hash,
                'period1_value' => $p1Value,
                'period2_value' => $p2Value,
                'absolute_delta' => $delta,
                'percent_delta' => round($deltaPercent, 2),
                'trend' => $delta > 0 ? 'up' : ($delta < 0 ? 'down' : 'flat'),
                'change_magnitude' => abs($deltaPercent),
            ];
        }

        // Сортировать по абсолютному изменению (самые большие изменения сверху)
        usort($comparisonData, fn($a, $b) => $b['change_magnitude'] <=> $a['change_magnitude']);

        $period1Days = max(1, $period1From->diffInDays($period1To));
        $period2Days = max(1, $period2From->diffInDays($period2To));

        $absoluteDelta = $period2Total - $period1Total;
        $percentDelta = $period1Total > 0 ? ($absoluteDelta / $period1Total) * 100 : 0;

        return [
            'period1_total' => $period1Total,
            'period1_avg' => round($period1Total / $period1Days, 2),
            'period2_total' => $period2Total,
            'period2_avg' => round($period2Total / $period2Days, 2),
            'absolute_delta' => $absoluteDelta,
            'percent_delta' => round($percentDelta, 2),
            'trend' => $absoluteDelta > 0 ? 'up' : ($absoluteDelta < 0 ? 'down' : 'flat'),
            'direction' => match(true) {
                abs($percentDelta) > 50 => 'significant',
                abs($percentDelta) > 20 => 'moderate',
                abs($percentDelta) > 5 => 'minor',
                default => 'stable',
            },
            'comparison_data' => array_slice($comparisonData, 0, 100), // Топ 100
        ];
    }

    /**
     * Вычислить дельта-различия для клик-данных
     */
    private function calculateClickComparison(
        array $period1Data,
        array $period2Data,
        Carbon $period1From,
        Carbon $period1To,
        Carbon $period2From,
        Carbon $period2To,
    ): array {
        $period1Map = collect($period1Data)->keyBy(fn($item) => "{$item['x']}-{$item['y']}")->toArray();
        $period2Map = collect($period2Data)->keyBy(fn($item) => "{$item['x']}-{$item['y']}")->toArray();

        $allCoords = array_unique(array_merge(array_keys($period1Map), array_keys($period2Map)));

        $heatmapComparison = [];
        $period1Total = 0;
        $period2Total = 0;

        foreach ($allCoords as $coord) {
            $p1Clicks = $period1Map[$coord]['click_count'] ?? 0;
            $p2Clicks = $period2Map[$coord]['click_count'] ?? 0;

            $period1Total += $p1Clicks;
            $period2Total += $p2Clicks;

            $delta = $p2Clicks - $p1Clicks;
            $deltaPercent = $p1Clicks > 0 ? ($delta / $p1Clicks) * 100 : 0;

            [$x, $y] = explode('-', $coord);

            $heatmapComparison[] = [
                'x' => (int)$x,
                'y' => (int)$y,
                'period1_clicks' => $p1Clicks,
                'period2_clicks' => $p2Clicks,
                'absolute_delta' => $delta,
                'percent_delta' => round($deltaPercent, 2),
                'trend' => $delta > 0 ? 'up' : ($delta < 0 ? 'down' : 'flat'),
            ];
        }

        $period1Days = max(1, $period1From->diffInDays($period1To));
        $period2Days = max(1, $period2From->diffInDays($period2To));

        $absoluteDelta = $period2Total - $period1Total;
        $percentDelta = $period1Total > 0 ? ($absoluteDelta / $period1Total) * 100 : 0;

        return [
            'period1_total' => $period1Total,
            'period1_avg' => round($period1Total / $period1Days, 2),
            'period2_total' => $period2Total,
            'period2_avg' => round($period2Total / $period2Days, 2),
            'absolute_delta' => $absoluteDelta,
            'percent_delta' => round($percentDelta, 2),
            'trend' => $absoluteDelta > 0 ? 'up' : ($absoluteDelta < 0 ? 'down' : 'flat'),
            'heatmap_comparison' => $heatmapComparison,
        ];
    }

    /**
     * Инвалидировать кэш для тенанта
     */
    public function invalidateCache(int $tenantId, string $vertical = '*'): void
    {
        Cache::tags(['heatmap_comparison', "tenant:{$tenantId}"])->flush();
        
        Log::channel('analytics')->info('Comparison cache invalidated', [
            'correlation_id' => $this->correlationId,
            'tenant_id' => $tenantId,
            'vertical' => $vertical,
        ]);
    }

    /**
     * Установить correlation ID для трассировки
     */
    public function setCorrelationId(string $id): void
    {
        $this->correlationId = $id;
        $this->clickHouseService->setCorrelationId($id);
    }

    /**
     * Построить ключ кэша для геоданных
     */
    private function buildGeoCacheKey(
        int $tenantId,
        string $vertical,
        Carbon $p1From,
        Carbon $p1To,
        Carbon $p2From,
        Carbon $p2To,
        string $metric,
    ): string {
        $key = "comparison:geo:tenant:{$tenantId}:vertical:{$vertical}:p1:{$p1From->format('Y-m-d')}:{$p1To->format('Y-m-d')}:p2:{$p2From->format('Y-m-d')}:{$p2To->format('Y-m-d')}:metric:{$metric}:v1";
        return $key;
    }

    /**
     * Построить ключ кэша для клик-данных
     */
    private function buildClickCacheKey(
        int $tenantId,
        string $vertical,
        string $pageUrl,
        Carbon $p1From,
        Carbon $p1To,
        Carbon $p2From,
        Carbon $p2To,
    ): string {
        $urlHash = md5($pageUrl);
        $key = "comparison:click:tenant:{$tenantId}:vertical:{$vertical}:url:{$urlHash}:p1:{$p1From->format('Y-m-d')}:{$p1To->format('Y-m-d')}:p2:{$p2From->format('Y-m-d')}:{$p2To->format('Y-m-d')}:v1";
        return $key;
    }
}
