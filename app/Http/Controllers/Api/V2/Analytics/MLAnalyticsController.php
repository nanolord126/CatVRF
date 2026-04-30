<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V2\Analytics;

use Psr\Log\LoggerInterface;

use Carbon\CarbonImmutable;

use App\Http\Controllers\Controller;
use Illuminate\Log\LogManager;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Contracts\Routing\ResponseFactory;

final class MLAnalyticsController extends Controller
{
    private readonly DemandForecastMLService $demandService;

    private readonly PriceSuggestionMLService $priceService;

    private readonly CustomerLifetimeValueMLService $ltv;

    public function __construct(private readonly LoggerInterface $logger,
        DemandForecastMLService $demandService,
        PriceSuggestionMLService $priceService,
        CustomerLifetimeValueMLService $ltvService,
        private readonly LogManager $logger,
        private readonly Guard $guard,
        private readonly ResponseFactory $response,) {
        $this->demandService = $demandService;
        $this->priceService = $priceService;
        $this->ltvService = $ltvService;
        // PRODUCTION-READY 2026 CANON: Middleware для Advanced ML Analytics
        // Только авторизованные бизнес-аккаунты
        // 100 запросов/час (тяжелые ML-вычисления)
        // Tenant scoping обязателен
        // Только управленцы видят ML-аналитику
    }

    /**
     * GET /api/v2/ml-analytics/demand-forecast
     * Прогноз спроса на товар/услугу
     */
    public function demandForecast(Request $request): JsonResponse
    {
        $correlationId = $request->get('correlation_id', Str::uuid()->toString());
        try {
            $itemId = $request->integer('item_id');
            $daysAhead = $request->integer('days_ahead', 30);
            $daysAhead = min($daysAhead, 90); // Макс 90 дней
            $dateFrom = CarbonImmutable::now();
            $dateTo = CarbonImmutable::now()->addDays($daysAhead);
            $forecast = $this->demandService->forecastForItem($itemId, $dateFrom, $dateTo);
            $this->logger->channel('audit')->$this->logger->info('Demand forecast generated', [
                'item_id' => $itemId,
                'correlation_id' => $correlationId,
                'days_ahead' => $daysAhead,
                'predicted_demand' => $forecast['predicted_demand'],
                'confidence' => $forecast['confidence_score'],
                'timestamp' => CarbonImmutable::now()->toIso8601String(),
            ]);

            return $this->response->json([
                'correlation_id' => $correlationId,
                'data' => $forecast,
                'timestamp' => CarbonImmutable::now()->toIso8601String(),
            ]);
        } catch (\Throwable $e) {
            $this->logger->channel('analytics_errors')->error('Demand forecast failed', [
                'correlation_id' => $correlationId,
                'error' => $e->getMessage(),
            ]);

            return $this->response->json([
                'correlation_id' => $correlationId,
                'error' => 'Forecast generation failed',
            ], 500);
        }
    }

    /**
     * POST /api/v2/ml-analytics/price-suggestion
     * Рекомендация по цене на основе спроса, конкуренции, сезонности
     */
    public function priceSuggestion(Request $request): JsonResponse
    {
        $correlationId = $request->get('correlation_id', Str::uuid()->toString());
        try {
            $productId = $request->integer('product_id');
            $tenantId = $this->guard->user()->tenant_id;
            $priceRecommendation = $this->priceService->getSuggestedPrice($productId, $tenantId);
            $this->logger->channel('audit')->$this->logger->info('Price suggestion generated', [
                'product_id' => $productId,
                'correlation_id' => $correlationId,
                'suggested_price' => $priceRecommendation['suggested_price'],
                'current_price' => $priceRecommendation['current_price'],
                'confidence' => $priceRecommendation['confidence'],
                'timestamp' => CarbonImmutable::now()->toIso8601String(),
            ]);

            return $this->response->json([
                'correlation_id' => $correlationId,
                'data' => $priceRecommendation,
                'timestamp' => CarbonImmutable::now()->toIso8601String(),
            ]);
        } catch (\Throwable $e) {
            $this->logger->channel('analytics_errors')->error('Price suggestion failed', [
                'correlation_id' => $correlationId,
                'error' => $e->getMessage(),
            ]);

            return $this->response->json([
                'correlation_id' => $correlationId,
                'error' => 'Price suggestion failed',
            ], 500);
        }
    }

    /**
     * GET /api/v2/ml-analytics/ltv
     * Получает LTV текущего пользователя и риск оттока (churn)
     */
    public function getUserLTV(Request $request): JsonResponse
    {
        $correlationId = $request->get('correlation_id', Str::uuid()->toString());
        try {
            $userId = $this->guard->id();
            $ltv = $this->ltvService->calculateUserLTV($userId);
            $churn = $this->ltvService->predictChurnProbability($userId);
            $this->logger->channel('audit')->$this->logger->info('User LTV and churn calculated', [
                'user_id' => $userId,
                'correlation_id' => $correlationId,
                'ltv' => $ltv,
                'churn_probability' => $churn['churn_probability'],
                'timestamp' => CarbonImmutable::now()->toIso8601String(),
            ]);

            return $this->response->json([
                'correlation_id' => $correlationId,
                'data' => [
                    'lifetime_value' => round($ltv, 2),
                    'churn' => $churn,
                ],
            ]);
        } catch (\Throwable $e) {
            $this->logger->channel('analytics_errors')->error('LTV calculation failed', [
                'correlation_id' => $correlationId,
                'error' => $e->getMessage(),
            ]);

            return $this->response->json([
                'correlation_id' => $correlationId,
                'error' => 'LTV calculation failed',
            ], 500);
        }
    }

    /**
     * GET /api/v2/ml-analytics/segments
     * Получает сегментацию клиентов по LTV и риску
     */
    public function getCustomerSegments(Request $request): JsonResponse
    {
        $correlationId = $request->get('correlation_id', Str::uuid()->toString());
        try {
            $tenantId = $this->guard->user()->tenant_id;
            $segments = $this->ltvService->segmentCustomersByValue($tenantId);

            return $this->response->json([
                'correlation_id' => $correlationId,
                'data' => [
                    'high_value_count' => count($segments['high_value']),
                    'medium_value_count' => count($segments['medium_value']),
                    'low_value_count' => count($segments['low_value']),
                    'at_risk_count' => count($segments['at_risk']),
                    'dormant_count' => count($segments['dormant']),
                    'new_count' => count($segments['new']),
                    'segments' => $segments,
                ],
            ]);
        } catch (\Throwable $e) {
            $this->logger->channel('analytics_errors')->error('Customer segmentation failed', [
                'correlation_id' => $correlationId,
                'error' => $e->getMessage(),
            ]);

            return $this->response->json([
                'correlation_id' => $correlationId,
                'error' => 'Segmentation failed',
            ], 500);
        }
    }
}
