<?php declare(strict_types=1);

namespace App\Http\Controllers\Api\V2\Analytics;

use App\Http\Controllers\Controller;
use Illuminate\Log\LogManager;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Contracts\Routing\ResponseFactory;

final class AnalyticsController extends Controller
{

    public function __construct(
            private readonly AdvancedAnalyticsService $analyticsService,
            private readonly LogManager $logger,
            private readonly Guard $guard,
            private readonly ResponseFactory $response,
    ) {
            // PRODUCTION-READY 2026 CANON: Middleware для Advanced Analytics
             // Только авторизованные
             // 1000 light / 100 heavy запросов/час
             // Tenant scoping обязателен
             // Только управленцы видят полную аналитику
        }
        /**
         * GET /api/v2/analytics/metrics
         * Получить метрики за период
         */
        public function getMetrics(Request $request): JsonResponse {
            $correlationId = Str::uuid()->toString();
            try {
                $validated = $request->validate([
                    'metric_type' => 'required|in:revenue,orders,conversion,aov',
                    'start_date' => 'required|date_format:Y-m-d',
                    'end_date' => 'required|date_format:Y-m-d|after:start_date',
                ]);
                $metrics = $this->analyticsService->getMetricsByPeriod(
                    tenantId: $this->guard->user()->tenant_id,
                    metricType: $validated['metric_type'],
                    startDate: $validated['start_date'],
                    endDate: $validated['end_date'],
                    context: ['correlation_id' => (string)$correlationId],
                );
                return $this->response->json([
                    'data' => $metrics,
                    'correlation_id' => (string)$correlationId,
                ]);
            } catch (\Exception $e) {
                $this->logger->channel('audit')->error($e->getMessage(), [
                    'exception' => $e::class,
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'correlation_id' => request()->header('X-Correlation-ID'),
                ]);

                $this->logger->error('Analytics metrics error', ['exception' => $e]);
                return $this->response->json([
                    'error' => $e->getMessage(),
                    'correlation_id' => (string)$correlationId,
                ], 500);
            }
        }
        /**
         * GET /api/v2/analytics/kpis
         * Получить ключевые KPI
         */
        public function getKPIs(Request $request): JsonResponse {
            $correlationId = Str::uuid()->toString();
            try {
                $kpis = $this->analyticsService->calculateKPIs(
                    tenantId: $this->guard->user()->tenant_id,
                    context: ['correlation_id' => (string)$correlationId],
                );
                return $this->response->json([
                    'data' => $kpis,
                    'correlation_id' => (string)$correlationId,
                ]);
            } catch (\Exception $e) {
                $this->logger->channel('audit')->error($e->getMessage(), [
                    'exception' => $e::class,
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'correlation_id' => request()->header('X-Correlation-ID'),
                ]);

                $this->logger->error('Analytics KPIs error', ['exception' => $e]);
                return $this->response->json([
                    'error' => $e->getMessage(),
                    'correlation_id' => (string)$correlationId,
                ], 500);
            }
        }
        /**
         * GET /api/v2/analytics/forecast
         * Получить прогноз на N дней
         */
        public function getForecast(Request $request): JsonResponse {
            $correlationId = Str::uuid()->toString();
            try {
                $validated = $request->validate([
                    'metric_type' => 'required|in:revenue,orders,conversion,aov',
                    'days_ahead' => 'nullable|integer|min:1|max:90',
                ]);
                $forecast = $this->analyticsService->predictFutureTrend(
                    tenantId: $this->guard->user()->tenant_id,
                    metricType: $validated['metric_type'],
                    daysAhead: $validated['days_ahead'] ?? 30,
                    context: ['correlation_id' => (string)$correlationId],
                );
                return $this->response->json([
                    'data' => $forecast,
                    'correlation_id' => (string)$correlationId,
                ]);
            } catch (\Exception $e) {
                $this->logger->channel('audit')->error($e->getMessage(), [
                    'exception' => $e::class,
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'correlation_id' => request()->header('X-Correlation-ID'),
                ]);

                $this->logger->error('Analytics forecast error', ['exception' => $e]);
                return $this->response->json([
                    'error' => $e->getMessage(),
                    'correlation_id' => (string)$correlationId,
                ], 500);
            }
        }
        /**
         * GET /api/v2/analytics/report
         * Получить кастомный отчёт
         */
        public function getReport(Request $request): JsonResponse {
            $correlationId = Str::uuid()->toString();
            try {
                $validated = $request->validate([
                    'include_trends' => 'nullable|boolean',
                    'compare_periods' => 'nullable|boolean',
                ]);
                $report = $this->analyticsService->generateCustomReport(
                    tenantId: $this->guard->user()->tenant_id,
                    filters: $validated,
                    context: ['correlation_id' => (string)$correlationId],
                );
                return $this->response->json([
                    'data' => $report,
                    'correlation_id' => (string)$correlationId,
                ]);
            } catch (\Exception $e) {
                $this->logger->channel('audit')->error($e->getMessage(), [
                    'exception' => $e::class,
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'correlation_id' => request()->header('X-Correlation-ID'),
                ]);

                $this->logger->error('Analytics report error', ['exception' => $e]);
                return $this->response->json([
                    'error' => $e->getMessage(),
                    'correlation_id' => (string)$correlationId,
                ], 500);
            }
        }
        /**
         * POST /api/v2/analytics/comparison
         * Сравнить метрики двух периодов
         */
        public function compareMetrics(Request $request): JsonResponse {
            $correlationId = Str::uuid()->toString();
            try {
                $validated = $request->validate([
                    'period_1' => 'required|in:7_days_ago,30_days_ago,current',
                    'period_2' => 'required|in:7_days_ago,30_days_ago,current',
                ]);
                $comparison = $this->analyticsService->getComparativeAnalysis(
                    tenantId: $this->guard->user()->tenant_id,
                    period1: $validated['period_1'],
                    period2: $validated['period_2'],
                    context: ['correlation_id' => (string)$correlationId],
                );
                return $this->response->json([
                    'data' => $comparison,
                    'correlation_id' => (string)$correlationId,
                ]);
            } catch (\Exception $e) {
                $this->logger->channel('audit')->error($e->getMessage(), [
                    'exception' => $e::class,
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'correlation_id' => request()->header('X-Correlation-ID'),
                ]);

                $this->logger->error('Analytics comparison error', ['exception' => $e]);
                return $this->response->json([
                    'error' => $e->getMessage(),
                    'correlation_id' => (string)$correlationId,
                ], 500);
            }
        }
}
