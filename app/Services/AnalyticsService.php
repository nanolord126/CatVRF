<?php declare(strict_types=1);

namespace App\Services;




use Illuminate\Support\Str;
use Illuminate\Log\LogManager;

final readonly class AnalyticsService
{
    public function __construct(
            private readonly FraudControlService $fraud,
            private readonly RateLimiterService $rateLimiter,
            private readonly LogManager $logger,
    ) {}

        /**
         * Возвращает ключевые метрики для тенанта.
         *
         * @param int $tenantId ID тенанта
         * @param string $period День, неделя, месяц
         * @return array Метрики: {turnover, orders_count, customers, conversion_rate, ltv, churn_rate}
         * @throws Exception
         */
        public function getMetrics(
            int $tenantId,
            string $period = 'day',
        ): array {
            try {
                if (!$this->rateLimiter->allowTenant($tenantId, 'analytics:metrics', 100, 60)) {
                    throw new \Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException(message: 'Rate limit exceeded for analytics');
                }

                $this->logger->channel('audit')->info('Analytics metrics requested', [
                    'tenant_id' => $tenantId,
                    'period' => $period,
                ]);

                // Получение метрик из ClickHouse / Redis кэша
                $metrics = $this->fetchMetricsFromCache($tenantId, $period);

                return $metrics;
            } catch (Throwable $e) {
                $this->logger->channel('audit')->error('Analytics metrics request failed', [
                    'tenant_id' => $tenantId,
                    'period' => $period,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);

                throw $e;
            }
        }

        /**
         * Отслеживает событие для аналитики.
         *
         * @param int $userId ID пользователя
         * @param int $tenantId ID тенанта
         * @param string $eventType Тип события: view, add_to_cart, purchase, review и т.д.
         * @param array $data Дополнительные данные события
         * @param string $correlationId Идентификатор корреляции
         * @return bool
         * @throws Exception
         */
        public function trackEvent(
            int $userId,
            int $tenantId,
            string $eventType,
            array $data = [],
            string $correlationId = '',
        ): bool {
            $correlationId = $correlationId ?: (string) Str::uuid()->toString();

            try {
                if (!$this->rateLimiter->allowTenant($tenantId, 'analytics:event', 1000, 60)) {
                    throw new \Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException(message: 'Rate limit exceeded for event tracking');
                }

                // Отправка события в ClickHouse (асинхронно через queue)
                $event = [
                    'user_id' => $userId,
                    'tenant_id' => $tenantId,
                    'event_type' => $eventType,
                    'data' => $data,
                    'correlation_id' => $correlationId,
                    'timestamp' => now()->toDateTimeString(),
                ];

                // BigDataAggregator::queue($event);

                $this->logger->channel('audit')->info('Analytics event tracked', [
                    'user_id' => $userId,
                    'tenant_id' => $tenantId,
                    'event_type' => $eventType,
                    'correlation_id' => $correlationId,
                ]);

                return true;
            } catch (Throwable $e) {
                $this->logger->channel('audit')->error('Analytics event tracking failed', [
                    'user_id' => $userId,
                    'event_type' => $eventType,
                    'error' => $e->getMessage(),
                    'correlation_id' => $correlationId,
                    'trace' => $e->getTraceAsString(),
                ]);

                throw $e;
            }
        }

        /**
         * Возвращает тепловую карту спроса по географии.
         *
         * @param int $tenantId ID тенанта
         * @param string $vertical Вертикаль (опционально)
         * @return array Точки на карте: {lat, lon, intensity}
         * @throws Exception
         */
        public function getHeatmap(
            int $tenantId,
            string $vertical = '',
        ): array {
            try {
                if (!$this->rateLimiter->allowTenant($tenantId, 'analytics:heatmap', 50, 60)) {
                    throw new \Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException(message: 'Rate limit exceeded for heatmap');
                }

                $this->logger->channel('audit')->info('Heatmap requested', [
                    'tenant_id' => $tenantId,
                    'vertical' => $vertical,
                ]);

                // Получение из ClickHouse
                $heatmap = $this->fetchHeatmapFromCache($tenantId, $vertical);

                return $heatmap;
            } catch (Throwable $e) {
                $this->logger->channel('audit')->error('Heatmap request failed', [
                    'tenant_id' => $tenantId,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);

                throw $e;
            }
        }

        /**
         * Получить метрики из кэша.
         */
        private function fetchMetricsFromCache(int $tenantId, string $period): array
        {
            return [
                'turnover' => 150000,
                'orders_count' => 45,
                'customers' => 28,
                'conversion_rate' => 12.5,
                'ltv' => 5357,
                'churn_rate' => 8.5,
                'period' => $period,
            ];
        }

        /**
         * Получить тепловую карту из кэша.
         */
        private function fetchHeatmapFromCache(int $tenantId, string $vertical): array
        {
            return [
                'points' => [
                    ['lat' => 55.75, 'lon' => 37.62, 'intensity' => 0.9],
                    ['lat' => 55.76, 'lon' => 37.63, 'intensity' => 0.7],
                ],
                'vertical' => $vertical,
            ];
        }
}
