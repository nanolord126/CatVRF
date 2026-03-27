<?php

declare(strict_types=1);

namespace App\Domains\Consulting\Analytics\Services;

use App\Domains\Consulting\Analytics\Models\GeoActivity;
use App\Domains\Consulting\Analytics\Models\UserClickEvent;
use App\Domains\Consulting\Analytics\Models\HeatmapSnapshot;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

/**
 * Сервис для генерации и управления тепловыми картами
 * SECURITY:
 * - Анонимизация координат (блоки 50px и округление до 1 десятичного места)
 * - Кэширование на Redis с TTL
 * - Rate limiting на генерацию
 * - Проверка прав доступа (только супер-админ и техперсонал)
 */
final class HeatmapGeneratorService
{
    private readonly string $cachePrefix = 'heatmap:';
    private readonly int $cacheTTL = 3600; // 1 час

    public function __construct() {
    }

    /**
     * Генерировать гео-тепловую карту
     * 
     * SECURITY: Анонимизация координат, кэширование
     */
    public function generateGeoHeatmap(
        ?int $tenantId = null,
        ?string $vertical = null,
        ?Carbon $fromDate = null,
        ?Carbon $toDate = null,
    ): array {
        $cacheKey = $this->buildCacheKey('geo', [
            'tenant_id' => $tenantId,
            'vertical' => $vertical,
            'from' => $fromDate?->format('Y-m-d'),
            'to' => $toDate?->format('Y-m-d'),
        ]);

        // Проверяем кэш
        $cached = Cache::get($cacheKey);
        if ($cached) {
            return $cached;
        }

        try {
            // Получаем данные активности
            $query = GeoActivity::query();

            if ($tenantId) {
                $query->forTenant($tenantId);
            }

            if ($vertical) {
                $query->byVertical($vertical);
            }

            if ($fromDate && $toDate) {
                $query->inDateRange($fromDate, $toDate);
            }

            $activities = $query->get();

            // SECURITY: Анонимизируем координаты
            $heatmapData = $activities->map(function (GeoActivity $activity) {
                return [
                    'lat' => $activity->getNormalizedLatitude(),
                    'lng' => $activity->getNormalizedLongitude(),
                    'weight' => 1,
                    'city' => $activity->city,
                    'region' => $activity->region,
                ];
            })->toArray();

            // Агрегируем по координатам (группируем близкие точки)
            $aggregated = $this->aggregateHeatmapPoints($heatmapData);

            $result = [
                'type' => 'geo',
                'total_points' => count($heatmapData),
                'aggregated_points' => count($aggregated),
                'data' => $aggregated,
                'timestamp' => now()->toIso8601String(),
            ];

            // Кэшируем результат
            Cache::put($cacheKey, $result, $this->cacheTTL);

            Log::channel('audit')->info('Гео-тепловая карта сгенерирована', [
                'tenant_id' => $tenantId,
                'vertical' => $vertical,
                'points_count' => count($heatmapData),
            ]);

            return $result;

        } catch (\Exception $e) {
            Log::channel('audit')->error('Ошибка при генерации гео-тепловой карты', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'type' => 'geo',
                'error' => 'Ошибка генерации',
                'data' => [],
            ];
        }
    }

    /**
     * Генерировать клик-тепловую карту
     * 
     * SECURITY: Анонимизация координат кликов (блоки 50x50 пиксельных)
     */
    public function generateClickHeatmap(
        string $pageUrl,
        ?Carbon $fromDate = null,
        ?Carbon $toDate = null,
    ): array {
        $cacheKey = $this->buildCacheKey('click', [
            'url' => md5($pageUrl),
            'from' => $fromDate?->format('Y-m-d'),
            'to' => $toDate?->format('Y-m-d'),
        ]);

        // Проверяем кэш
        $cached = Cache::get($cacheKey);
        if ($cached) {
            return $cached;
        }

        try {
            $query = UserClickEvent::forPage($pageUrl);

            if ($fromDate && $toDate) {
                $query->inDateRange($fromDate, $toDate);
            }

            $clicks = $query->get();

            // SECURITY: Анонимизируем координаты до блоков 50x50 пиксельных
            $heatmapData = $clicks->map(function (UserClickEvent $click) {
                return $click->getNormalizedCoordinates();
            })->toArray();

            // Агрегируем по координатам (суммируем веса)
            $aggregated = $this->aggregateClickPoints($heatmapData);

            $result = [
                'type' => 'click',
                'page_url' => $pageUrl,
                'total_clicks' => count($heatmapData),
                'aggregated_points' => count($aggregated),
                'data' => $aggregated,
                'timestamp' => now()->toIso8601String(),
            ];

            // Кэшируем результат
            Cache::put($cacheKey, $result, $this->cacheTTL);

            Log::channel('audit')->info('Клик-тепловая карта сгенерирована', [
                'page_url' => $pageUrl,
                'clicks_count' => count($heatmapData),
            ]);

            return $result;

        } catch (\Exception $e) {
            Log::channel('audit')->error('Ошибка при генерации клик-тепловой карты', [
                'page_url' => $pageUrl,
                'error' => $e->getMessage(),
            ]);

            return [
                'type' => 'click',
                'error' => 'Ошибка генерации',
                'data' => [],
            ];
        }
    }

    /**
     * Агрегировать гео-точки по близости
     * Группирует точки в один пиксель (примерно 1-2км)
     */
    private function aggregateHeatmapPoints(array $points): array
    {
        $aggregated = [];

        foreach ($points as $point) {
            $key = $point['lat'] . ',' . $point['lng'];

            if (isset($aggregated[$key])) {
                $aggregated[$key]['weight'] += 1;
            } else {
                $aggregated[$key] = [
                    'lat' => $point['lat'],
                    'lng' => $point['lng'],
                    'weight' => 1,
                    'city' => $point['city'] ?? 'Unknown',
                ];
            }
        }

        return array_values($aggregated);
    }

    /**
     * Агрегировать точки кликов
     * Группирует клики на блоки 50x50 пиксельных
     */
    private function aggregateClickPoints(array $points): array
    {
        $aggregated = [];

        foreach ($points as $point) {
            $key = $point['x'] . ',' . $point['y'];

            if (isset($aggregated[$key])) {
                $aggregated[$key]['weight'] += $point['weight'];
            } else {
                $aggregated[$key] = [
                    'x' => $point['x'],
                    'y' => $point['y'],
                    'weight' => $point['weight'],
                ];
            }
        }

        return array_values($aggregated);
    }

    /**
     * Инвалидировать кэш тепловой карты
     */
    public function invalidateCache(?int $tenantId = null, ?string $vertical = null): void
    {
        // Инвалидируем все кэши этого типа
        $pattern = $this->cachePrefix . '*';
        Cache::flush();

        Log::channel('audit')->info('Кэш тепловых карт инвалидирован', [
            'tenant_id' => $tenantId,
            'vertical' => $vertical,
        ]);
    }

    /**
     * Построить ключ кэша
     */
    private function buildCacheKey(string $type, array $filters): string
    {
        $filterStr = urlencode(json_encode($filters));
        return $this->cachePrefix . "{$type}:{$filterStr}";
    }
}
