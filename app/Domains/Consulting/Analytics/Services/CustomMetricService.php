<?php declare(strict_types=1);

namespace App\Domains\Consulting\Analytics\Services;

use Carbon\Carbon;


use Psr\Log\LoggerInterface;
final readonly class CustomMetricService
{

    private readonly string $correlationId;


    public function __construct(private readonly ClickHouseService $clickHouseService,
        private readonly \Illuminate\Cache\CacheManager $cache, private readonly LoggerInterface $logger) {

    }

        string $correlationId = '';

        /**
         * Получить кастомную метрику для геоданных
         *
         * @param int $tenantId
         * @param string $vertical
         * @param Carbon $fromDate
         * @param Carbon $toDate
         * @param string $metricType Тип метрики (revenue, conversion, ctr, roi и т.д.)
         * @param string $aggregation hourly|daily|weekly
         * @param array $params Дополнительные параметры (revenue_field, conversion_field и т.д.)
         * @return array Результат с данными метрики
         */
        public function getGeoCustomMetric(
            int $tenantId,
            string $vertical,
            Carbon $fromDate,
            Carbon $toDate,
            string $metricType,
            string $aggregation = 'daily',
            array $params = []
    ): array {
            $cacheKey = $this->buildGeoCacheKey($tenantId, $vertical, $fromDate, $toDate, $metricType, $aggregation);

            // Проверить кэш
            $cached = cache()->get($cacheKey);
            if ($cached) {
                $this->logger->debug('Geo custom metric cache hit', [
                    'correlation_id' => $this->correlationId,
                    'metric_type' => $metricType,
                ]);
                return $cached;
            }

            try {
                // Получить базовые данные
                $baseData = match($aggregation) {
                    'weekly' => $this->clickHouseService->queryGeoWeekly($tenantId, $vertical, [$fromDate, $toDate]),
                    default => $this->clickHouseService->queryGeoDaily($tenantId, $vertical, [$fromDate, $toDate], 'event_count'),
                };

                // Вычислить метрику
                $metricData = match($metricType) {
                    'engagement_score' => $this->calculateEngagementScore($baseData),
                    'growth_rate' => $this->calculateGrowthRate($baseData),
                    'hotspot_concentration' => $this->calculateHotspotConcentration($baseData),
                    'user_retention' => $this->calculateUserRetention($baseData),
                    default => ['error' => 'Unknown metric type'],
                };

                $response = [
                    'metric_type' => $metricType,
                    'aggregation' => $aggregation,
                    'period' => [
                        'from' => $fromDate->toIso8601String(),
                        'to' => $toDate->toIso8601String(),
                    ],
                    'data' => $metricData,
                    'metadata' => [
                        'generated_at' => Carbon::now()->toIso8601String(),
                        'correlation_id' => $this->correlationId,
                    ],
                ];

                // Кэшировать (5m/1h/24h в зависимости от типа)
                $ttl = match($aggregation) {
                    'weekly' => 86400,
                    default => 3600,
                };

                cache()->put($cacheKey, $response, $ttl);

                $this->logger->info('Geo custom metric calculated', [
                    'correlation_id' => $this->correlationId,
                    'tenant_id' => $tenantId,
                    'metric_type' => $metricType,
                    'aggregation' => $aggregation,
                ]);

                return $response;
            } catch (\Throwable $e) {
                $this->logger->error('Geo custom metric calculation failed', [
                    'correlation_id' => $this->correlationId,
                    'metric_type' => $metricType,
                    'message' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);

                return [
                    'metric_type' => $metricType,
                    'error' => 'Не удалось вычислить метрику',
                    'correlation_id' => $this->correlationId,
                ];
            }
        }

        /**
         * Получить кастомную метрику для клик-данных
         *
         * @param int $tenantId
         * @param string $vertical
         * @param string $pageUrl
         * @param Carbon $fromDate
         * @param Carbon $toDate
         * @param string $metricType
         * @param string $aggregation
         * @return array
         */
        public function getClickCustomMetric(
            int $tenantId,
            string $vertical,
            string $pageUrl,
            Carbon $fromDate,
            Carbon $toDate,
            string $metricType,
            string $aggregation = 'daily'
    ): array {
            $cacheKey = $this->buildClickCacheKey($tenantId, $vertical, $pageUrl, $fromDate, $toDate, $metricType, $aggregation);

            // Проверить кэш
            $cached = cache()->get($cacheKey);
            if ($cached) {
                $this->logger->debug('Click custom metric cache hit', [
                    'correlation_id' => $this->correlationId,
                    'metric_type' => $metricType,
                ]);
                return $cached;
            }

            try {
                // Получить базовые данные
                $baseData = match($aggregation) {
                    default => $this->clickHouseService->queryClickDaily($tenantId, $vertical, $pageUrl, [$fromDate, $toDate]),
                };

                // Вычислить метрику
                $metricData = match($metricType) {
                    'interaction_score' => $this->calculateInteractionScore($baseData),
                    'user_engagement' => $this->calculateUserEngagement($baseData),
                    'click_conversion' => $this->calculateClickConversion($baseData),
                    default => ['error' => 'Unknown metric type'],
                };

                $response = [
                    'metric_type' => $metricType,
                    'aggregation' => $aggregation,
                    'page_url' => $pageUrl,
                    'period' => [
                        'from' => $fromDate->toIso8601String(),
                        'to' => $toDate->toIso8601String(),
                    ],
                    'data' => $metricData,
                    'metadata' => [
                        'generated_at' => Carbon::now()->toIso8601String(),
                        'correlation_id' => $this->correlationId,
                    ],
                ];

                // Кэшировать
                $ttl = match($aggregation) {
                    default => 3600,
                };

                cache()->put($cacheKey, $response, $ttl);

                $this->logger->info('Click custom metric calculated', [
                    'correlation_id' => $this->correlationId,
                    'tenant_id' => $tenantId,
                    'metric_type' => $metricType,
                    'page_url' => $pageUrl,
                ]);

                return $response;
            } catch (\Throwable $e) {
                $this->logger->error('Click custom metric calculation failed', [
                    'correlation_id' => $this->correlationId,
                    'metric_type' => $metricType,
                    'message' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);

                return [
                    'metric_type' => $metricType,
                    'error' => 'Не удалось вычислить метрику',
                    'correlation_id' => $this->correlationId,
                ];
            }
        }

        /**
         * Интенсивность событий (события/день/геохэш)
         */
        private function calculateEventIntensity(array $data, Carbon $from, Carbon $to): array
        {
            $days = max(1, $from->diffInDays($to));
            $byGeoHash = [];

            foreach ($data as $row) {
                $hash = $row['geo_hash'] ?? 'unknown';
                if (!isset($byGeoHash[$hash])) {
                    $byGeoHash[$hash] = 0;
                }
                $byGeoHash[$hash] += $row['event_count'] ?? 0;
            }

            $intensity = [];
            foreach ($byGeoHash as $hash => $count) {
                $intensity[] = [
                    'geo_hash' => $hash,
                    'daily_average' => round($count / $days, 2),
                    'total' => $count,
                    'intensity_level' => match(true) {
                        $count / $days > 100 => 'very_high',
                        $count / $days > 50 => 'high',
                        $count / $days > 20 => 'medium',
                        $count / $days > 5 => 'low',
                        default => 'very_low',
                    },
                ];
            }

            usort($intensity, fn($a, $b) => $b['daily_average'] <=> $a['daily_average']);

            return [
                'by_geohash' => array_slice($intensity, 0, 50),
                'summary' => [
                    'total_intensity' => array_sum($byGeoHash),
                    'avg_intensity' => round(array_sum($byGeoHash) / count($byGeoHash), 2),
                    'peak_intensity' => max($byGeoHash),
                    'hotspots' => count(array_filter($byGeoHash, fn($v) => $v > 50)),
                ],
            ];
        }

        /**
         * Оценка вовлечённости (комбинированная метрика)
         */
        private function calculateEngagementScore(array $data): array
        {
            $totalEvents = 0;
            $uniqueUsers = 0;
            $locations = 0;

            foreach ($data as $row) {
                $totalEvents += $row['event_count'] ?? 0;
                $uniqueUsers += $row['unique_users'] ?? 0;
                $locations++;
            }

            $avgEventsPerLocation = $locations > 0 ? $totalEvents / $locations : 0;
            $engagementScore = min(100, ($totalEvents / 1000) * (($uniqueUsers / 100) * 10));

            return [
                'engagement_score' => round($engagementScore, 2),
                'score_level' => match(true) {
                    $engagementScore >= 80 => 'excellent',
                    $engagementScore >= 60 => 'good',
                    $engagementScore >= 40 => 'moderate',
                    $engagementScore >= 20 => 'poor',
                    default => 'very_poor',
                },
                'metrics' => [
                    'total_events' => $totalEvents,
                    'unique_users' => $uniqueUsers,
                    'locations' => $locations,
                    'avg_events_per_location' => round($avgEventsPerLocation, 2),
                ],
            ];
        }

        /**
         * Темп роста (тренд)
         */
        private function calculateGrowthRate(array $data): array
        {
            if (count($data) < 2) {
                return ['growth_rate' => 0, 'trend' => 'flat'];
            }

            $first = $data[0]['event_count'] ?? 0;
            $last = $data[count($data) - 1]['event_count'] ?? 0;

            $growthRate = $first > 0 ? (($last - $first) / $first) * 100 : 0;

            return [
                'growth_rate' => round($growthRate, 2),
                'trend' => match(true) {
                    $growthRate > 20 => 'strong_growth',
                    $growthRate > 5 => 'growth',
                    $growthRate > -5 => 'stable',
                    $growthRate > -20 => 'decline',
                    default => 'strong_decline',
                },
                'first_period_value' => $first,
                'last_period_value' => $last,
            ];
        }

        /**
         * Концентрация горячих точек
         */
        private function calculateHotspotConcentration(array $data): array
        {
            $byGeoHash = [];
            $totalEvents = 0;

            foreach ($data as $row) {
                $hash = $row['geo_hash'] ?? 'unknown';
                $count = $row['event_count'] ?? 0;
                $byGeoHash[$hash] = ($byGeoHash[$hash] ?? 0) + $count;
                $totalEvents += $count;
            }

            arsort($byGeoHash);
            $top10 = array_slice($byGeoHash, 0, 10);
            $top10Total = array_sum($top10);

            $concentration = $totalEvents > 0 ? ($top10Total / $totalEvents) * 100 : 0;

            return [
                'concentration_percent' => round($concentration, 2),
                'concentration_level' => match(true) {
                    $concentration > 80 => 'very_high',
                    $concentration > 60 => 'high',
                    $concentration > 40 => 'moderate',
                    default => 'distributed',
                },
                'top_10_hotspots' => array_map(
                    fn($hash, $count) => [
                        'geo_hash' => $hash,
                        'events' => $count,
                        'percent' => round(($count / $totalEvents) * 100, 2),
                    ],
                    array_keys($top10),
                    array_values($top10)
                ),
                'total_locations' => count($byGeoHash),
            ];
        }

        /**
         * Удержание пользователей
         */
        private function calculateUserRetention(array $data): array
        {
            $uniqueUsers = [];
            foreach ($data as $row) {
                $uniqueUsers[] = $row['unique_users'] ?? 0;
            }

            if (empty($uniqueUsers)) {
                return ['retention_rate' => 0];
            }

            $firstUsers = $uniqueUsers[0];
            $lastUsers = $uniqueUsers[count($uniqueUsers) - 1];

            $retentionRate = $firstUsers > 0 ? ($lastUsers / $firstUsers) * 100 : 0;

            return [
                'retention_rate' => round($retentionRate, 2),
                'first_period_users' => $firstUsers,
                'last_period_users' => $lastUsers,
                'retention_status' => match(true) {
                    $retentionRate >= 80 => 'excellent',
                    $retentionRate >= 60 => 'good',
                    $retentionRate >= 40 => 'moderate',
                    default => 'poor',
                },
            ];
        }

        /**
         * Плотность кликов (клики/пиксель)
         */
        private function calculateClickDensity(array $data): array
        {
            if (empty($data)) {
                return ['density' => 0];
            }

            $byCoord = [];
            $totalClicks = 0;

            foreach ($data as $row) {
                $coord = "{$row['x']}-{$row['y']}";
                $clicks = $row['click_count'] ?? 0;
                $byCoord[$coord] = ($byCoord[$coord] ?? 0) + $clicks;
                $totalClicks += $clicks;
            }

            $density = count($byCoord) > 0 ? $totalClicks / count($byCoord) : 0;

            arsort($byCoord);
            $hotspots = array_slice($byCoord, 0, 20);

            return [
                'average_density' => round($density, 2),
                'total_clicks' => $totalClicks,
                'clickable_areas' => count($byCoord),
                'hotspots' => array_map(
                    fn($coord, $clicks) => [
                        'coordinates' => $coord,
                        'clicks' => $clicks,
                        'density' => round(($clicks / $totalClicks) * 100, 2),
                    ],
                    array_keys($hotspots),
                    array_values($hotspots)
                ),
            ];
        }

        /**
         * Оценка взаимодействия
         */
        private function calculateInteractionScore(array $data): array
        {
            $totalClicks = 0;
            $uniqueUsers = 0;
            $sessions = 0;

            foreach ($data as $row) {
                $totalClicks += $row['click_count'] ?? 0;
                $uniqueUsers += $row['unique_users'] ?? 0;
                $sessions += $row['unique_users'] ?? 0; // Approximation
            }

            $interactionScore = min(100, ($totalClicks / 100) * ($uniqueUsers / 10));

            return [
                'interaction_score' => round($interactionScore, 2),
                'score_level' => match(true) {
                    $interactionScore >= 80 => 'very_high',
                    $interactionScore >= 60 => 'high',
                    $interactionScore >= 40 => 'moderate',
                    $interactionScore >= 20 => 'low',
                    default => 'very_low',
                },
                'metrics' => [
                    'total_clicks' => $totalClicks,
                    'unique_users' => $uniqueUsers,
                    'clicks_per_user' => $uniqueUsers > 0 ? round($totalClicks / $uniqueUsers, 2) : 0,
                ],
            ];
        }

        /**
         * Вовлечённость пользователя
         */
        private function calculateUserEngagement(array $data): array
        {
            $userEngagements = [];

            foreach ($data as $row) {
                $users = $row['unique_users'] ?? 0;
                $clicks = $row['click_count'] ?? 0;
                if ($users > 0) {
                    $engagementPerUser = $clicks / $users;
                    $userEngagements[] = $engagementPerUser;
                }
            }

            $avgEngagement = !empty($userEngagements) ? array_sum($userEngagements) / count($userEngagements) : 0;

            return [
                'average_engagement_per_user' => round($avgEngagement, 2),
                'engagement_level' => match(true) {
                    $avgEngagement >= 10 => 'very_high',
                    $avgEngagement >= 5 => 'high',
                    $avgEngagement >= 2 => 'moderate',
                    $avgEngagement >= 1 => 'low',
                    default => 'very_low',
                },
            ];
        }

        /**
         * Конверсия по кликам (клики → действие)
         */
        private function calculateClickConversion(array $data): array
        {
            $totalClicks = 0;
            $uniqueUsers = 0;

            foreach ($data as $row) {
                $totalClicks += $row['click_count'] ?? 0;
                $uniqueUsers += $row['unique_users'] ?? 0;
            }

            $conversionRate = $uniqueUsers > 0 ? ($totalClicks / $uniqueUsers) * 100 : 0;

            return [
                'conversion_rate' => round($conversionRate, 2),
                'conversion_type' => match(true) {
                    $conversionRate > 50 => 'very_high',
                    $conversionRate > 30 => 'high',
                    $conversionRate > 10 => 'moderate',
                    $conversionRate > 5 => 'low',
                    default => 'very_low',
                },
                'total_clicks' => $totalClicks,
                'unique_users' => $uniqueUsers,
            ];
        }

        /**
         * Инвалидировать кэш
         */
        public function invalidateCache(int $tenantId): void
        {
            $this->cache->tags(['custom_metric', "tenant:{$tenantId}"])->flush();

            $this->logger->info('Custom metric cache invalidated', [
                'correlation_id' => $this->correlationId,
                'tenant_id' => $tenantId,
            ]);
        }

        /**
         * Установить correlation ID
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
            Carbon $from,
            Carbon $to,
            string $metricType,
            string $aggregation
    ): string {
            return "custom_metric:geo:tenant:{$tenantId}:vertical:{$vertical}:from:{$from->format('Y-m-d')}:to:{$to->format('Y-m-d')}:metric:{$metricType}:agg:{$aggregation}:v1";
        }

        /**
         * Построить ключ кэша для клик-данных
         */
        private function buildClickCacheKey(
            int $tenantId,
            string $vertical,
            string $pageUrl,
            Carbon $from,
            Carbon $to,
            string $metricType,
            string $aggregation
    ): string {
            $urlHash = md5($pageUrl);
            return "custom_metric:click:tenant:{$tenantId}:vertical:{$vertical}:url:{$urlHash}:from:{$from->format('Y-m-d')}:to:{$to->format('Y-m-d')}:metric:{$metricType}:agg:{$aggregation}:v1";
        }
}
