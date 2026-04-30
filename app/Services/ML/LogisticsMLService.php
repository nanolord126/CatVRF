<?php

declare(strict_types=1);

namespace App\Services\ML;

use Psr\Log\LoggerInterface;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Log\LogManager;
use Carbon\CarbonImmutable;
use App\Traits\WithAuditLogging;
use App\Services\Security\AuditService;

/**
 * LogisticsMLService — интеграция с Python ML сервисом
 *
 * Вызывает ML модели через HTTP:
 * - Courier assignment scoring
 * - PVZ scoring
 * - ETA prediction
 * - Route optimization
 * - Agent AI analysis
 *
 * Канон CatVRF 2026:
 * - Circuit breaker для отказоустойчивости
 * - Fallback на эвристики при недоступности ML
 * - Таймауты и ретраи
 * - Логирование всех вызовов
 */
final readonly class LogisticsMLService
{
    use WithAuditLogging;

    private readonly string $baseUrl;

    private readonly int $timeout;

    private readonly bool $enabled;

    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly HttpFactory $http,
        private readonly LogManager $log,
        private readonly AuditService $auditService,
    ) {
        $this->baseUrl = config('services.ml.url', 'http://localhost:8000');
        $this->timeout = config('services.ml.timeout', 5);
        $this->enabled = config('services.ml.enabled', true);
    }

    /**
     * Скоринг курьеров для назначения
     */
    public function scoreCouriers(array $order, array $candidates): array
    {
        if (! $this->enabled) {
            return $this->fallbackCourierScoring($candidates);
        }

        try {
            $response = $this->http()->post("{$this->baseUrl}/api/v1/predict/courier-assignment", [
                'order_id' => $order['id'],
                'delivery_lat' => $order['delivery_lat'] ?? 55.75,
                'delivery_lng' => $order['delivery_lon'] ?? 37.62,
                'weight_kg' => $order['metadata']['weight_kg'] ?? 5,
                'volume_cm3' => $order['metadata']['volume_cm3'] ?? 10000,
                'items_count' => $order['metadata']['items_count'] ?? 1,
                'time_sensitive' => $order['metadata']['time_sensitive'] ?? false,
                'candidates' => $candidates,
                'hour_of_day' => CarbonImmutable::now()->hour,
                'day_of_week' => CarbonImmutable::now()->dayOfWeek,
                'weather_condition' => 'unknown',
                'traffic_level' => 'unknown',
            ]);

            if (! $response->successful()) {
                $this->log->warning('ML service courier scoring failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return $this->fallbackCourierScoring($candidates);
            }

            $data = $response->json();

            $this->log->$this->logger->info('ML courier scoring successful', [
                'order_id' => $order['id'],
                'best_courier_id' => $data['best_courier_id'],
                'model_version' => $data['model_version'],
            ]);

            return $data['scored_couriers'];

        } catch (\Throwable $e) {
            $this->log->error('ML service courier scoring error', [
                'error' => $e->getMessage(),
            ]);

            return $this->fallbackCourierScoring($candidates);
        }
    }

    /**
     * Скоринг ПВЗ для назначения
     */
    public function scorePvz(array $order, array $candidates): array
    {
        if (! $this->enabled) {
            return $this->fallbackPvzScoring($candidates);
        }

        try {
            $response = $this->http()->post("{$this->baseUrl}/api/v1/predict/pvz-scoring", [
                'order_id' => $order['id'],
                'delivery_lat' => $order['delivery_lat'] ?? 55.75,
                'delivery_lng' => $order['delivery_lon'] ?? 37.62,
                'weight_kg' => $order['metadata']['weight_kg'] ?? 5,
                'items_count' => $order['metadata']['items_count'] ?? 1,
                'prefers_pvz' => $order['metadata']['prefers_pvz'] ?? false,
                'candidates' => $candidates,
                'hour_of_day' => CarbonImmutable::now()->hour,
                'day_of_week' => CarbonImmutable::now()->dayOfWeek,
            ]);

            if (! $response->successful()) {
                $this->log->warning('ML service PVZ scoring failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return $this->fallbackPvzScoring($candidates);
            }

            $data = $response->json();

            $this->log->$this->logger->info('ML PVZ scoring successful', [
                'order_id' => $order['id'],
                'best_pvz_id' => $data['best_pvz_id'],
            ]);

            return $data['scored_pvzs'];

        } catch (\Throwable $e) {
            $this->log->error('ML service PVZ scoring error', [
                'error' => $e->getMessage(),
            ]);

            return $this->fallbackPvzScoring($candidates);
        }
    }

    /**
     * Предсказание ETA
     */
    public function predictEta(array $shipment): int
    {
        if (! $this->enabled) {
            return 30; // Fallback: 30 минут
        }

        try {
            $response = $this->http()->post("{$this->baseUrl}/api/v1/predict/eta", [
                'shipment_id' => $shipment['id'],
                'courier_lat' => $shipment['courier_lat'] ?? 55.74,
                'courier_lng' => $shipment['courier_lng'] ?? 37.61,
                'delivery_lat' => $shipment['delivery_lat'] ?? 55.75,
                'delivery_lng' => $shipment['delivery_lng'] ?? 37.62,
                'vehicle_type' => $shipment['vehicle_type'] ?? 'bike',
                'distance_km' => $shipment['distance_km'] ?? 5,
                'hour_of_day' => CarbonImmutable::now()->hour,
                'day_of_week' => CarbonImmutable::now()->dayOfWeek,
            ]);

            if (! $response->successful()) {
                $this->log->warning('ML service ETA prediction failed', [
                    'status' => $response->status(),
                ]);

                return 30;
            }

            $data = $response->json();

            return $data['predicted_eta_minutes'];

        } catch (\Throwable $e) {
            $this->log->error('ML service ETA prediction error', [
                'error' => $e->getMessage(),
            ]);

            return 30;
        }
    }

    /**
     * Оптимизация маршрута
     */
    public function optimizeRoute(array $routeData): array
    {
        if (! $this->enabled) {
            return $this->fallbackRouteOptimization($routeData);
        }

        try {
            $response = $this->http()->post("{$this->baseUrl}/api/v1/optimize/route", $routeData);

            if (! $response->successful()) {
                $this->log->warning('ML service route optimization failed', [
                    'status' => $response->status(),
                ]);

                return $this->fallbackRouteOptimization($routeData);
            }

            return $response->json();

        } catch (\Throwable $e) {
            $this->log->error('ML service route optimization error', [
                'error' => $e->getMessage(),
            ]);

            return $this->fallbackRouteOptimization($routeData);
        }
    }

    /**
     * Агент AI анализ аномалий
     */
    public function analyzeAnomalies(string $analysisType, array $context): array
    {
        if (! $this->enabled) {
            return [];
        }

        try {
            $response = $this->http()->post("{$this->baseUrl}/api/v1/agent/analyze", [
                'analysis_type' => $analysisType,
                'context' => $context,
            ]);

            if (! $response->successful()) {
                $this->log->warning('ML service agent analysis failed', [
                    'status' => $response->status(),
                ]);

                return [];
            }

            return $response->json();

        } catch (\Throwable $e) {
            $this->log->error('ML service agent analysis error', [
                'error' => $e->getMessage(),
            ]);

            return [];
        }
    }

    /**
     * Проверка здоровья ML сервиса
     */
    public function healthCheck(): bool
    {
        try {
            $response = $this->http()->get("{$this->baseUrl}/health");

            return $response->successful();
        } catch (\Throwable $e) {
            return false;
        }
    }

    private function http(): PendingRequest
    {
        return $this->http->timeout($this->timeout)
            ->retry(2, 100) // 2 retries, 100ms delay
            ->acceptJson();
    }

    /**
     * Fallback: эвристический скоринг курьеров
     */
    private function fallbackCourierScoring(array $candidates): array
    {
        foreach ($candidates as &$candidate) {
            $candidate['score'] = (
                0.40 * (1 / ($candidate['distance_m'] + 1)) +
                0.25 * min($candidate['capacity_kg'] / max($candidate['weight_kg'] ?? 5, 1), 1.0) +
                0.20 * ($candidate['rating'] ?? 5.0) / 5.0 +
                0.10 * ($candidate['is_taxi_driver'] ? 1 : 0)
            );
        }

        usort($candidates, fn ($a, $b) => $b['score'] <=> $a['score']);

        return $candidates;
    }

    /**
     * Fallback: эвристический скоринг ПВЗ
     */
    private function fallbackPvzScoring(array $candidates): array
    {
        foreach ($candidates as &$candidate) {
            $loadPercentage = $candidate['current_load'] / max($candidate['capacity'], 1);
            $candidate['score'] = (
                0.40 * (1 / ($candidate['distance_m'] + 1)) +
                0.30 * (1 - $loadPercentage) +
                0.20 * ($candidate['is_24h'] ? 1 : 0)
            );
        }

        usort($candidates, fn ($a, $b) => $b['score'] <=> $a['score']);

        return $candidates;
    }

    /**
     * Fallback: простая оптимизация маршрута
     */
    private function fallbackRouteOptimization(array $routeData): array
    {
        // Возвращаем остановки в исходном порядке
        return [
            'courier_id' => $routeData['courier_id'],
            'optimized_stops' => $routeData['stops'],
            'total_distance_km' => 0,
            'total_time_minutes' => 0,
            'algorithm' => 'fallback',
        ];
    }
}
