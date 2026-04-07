<?php declare(strict_types=1);

namespace App\Domains\Consulting\Analytics\Services;

use Carbon\Carbon;


use Psr\Log\LoggerInterface;
use Illuminate\Http\Request;

final readonly class HeatmapGeneratorService
{


    private readonly string $cachePrefix;
        private readonly int $cacheTTL; // 1 час

        public function __construct(
        private readonly Request $request, private readonly LoggerInterface $logger, private readonly \Illuminate\Contracts\Cache\Repository $cache) {
        $this->cachePrefix = 'heatmap:';
        $this->cacheTTL = 3600;
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
            ?Carbon $toDate = null
    ): array {
            $cacheKey = $this->buildCacheKey('geo', [
                'tenant_id' => $tenantId,
                'vertical' => $vertical,
                'from' => $fromDate?->format('Y-m-d'),
                'to' => $toDate?->format('Y-m-d'),
            ]);

            // Проверяем кэш
            $cached = cache()->get($cacheKey);
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
                    'timestamp' => Carbon::now()->toIso8601String(),
                ];

                // Кэшируем результат
                cache()->put($cacheKey, $result, $this->cacheTTL);

                $this->logger->info('Гео-тепловая карта сгенерирована', [
                    'tenant_id' => $tenantId,
                    'vertical' => $vertical,
                    'points_count' => count($heatmapData),
                'correlation_id' => $this->request->header('X-Correlation-ID', $this->correlationId ?? ''),
            ]);

                return $result;

            } catch (\Throwable $e) {
                $this->logger->error('Ошибка при генерации гео-тепловой карты', [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                'correlation_id' => $this->request->header('X-Correlation-ID', $this->correlationId ?? ''),
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
            ?Carbon $toDate = null
    ): array {
            $cacheKey = $this->buildCacheKey('click', [
                'url' => md5($pageUrl),
                'from' => $fromDate?->format('Y-m-d'),
                'to' => $toDate?->format('Y-m-d'),
            ]);

            // Проверяем кэш
            $cached = cache()->get($cacheKey);
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
                    'timestamp' => Carbon::now()->toIso8601String(),
                ];

                // Кэшируем результат
                cache()->put($cacheKey, $result, $this->cacheTTL);

                $this->logger->info('Клик-тепловая карта сгенерирована', [
                    'page_url' => $pageUrl,
                    'clicks_count' => count($heatmapData),
                'correlation_id' => $this->request->header('X-Correlation-ID', $this->correlationId ?? ''),
            ]);

                return $result;

            } catch (\Throwable $e) {
                $this->logger->error('Ошибка при генерации клик-тепловой карты', [
                    'page_url' => $pageUrl,
                    'error' => $e->getMessage(),
                'correlation_id' => $this->request->header('X-Correlation-ID', $this->correlationId ?? ''),
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
            $this->cache->flush();

            $this->logger->info('Кэш тепловых карт инвалидирован', [
                'tenant_id' => $tenantId,
                'vertical' => $vertical,
                'correlation_id' => $this->request->header('X-Correlation-ID', $this->correlationId ?? ''),
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
