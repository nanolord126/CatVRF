<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V2\Analytics;

use App\Http\Controllers\Controller;
use App\Services\Analytics\DemandForecastMLService;
use App\Services\Analytics\PriceSuggestionMLService;
use App\Services\Analytics\CustomerLifetimeValueMLService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Carbon\Carbon;

/**
 * Advanced ML Analytics Controller
 * Эндпоинты для прогнозирования спроса, ценообразования и анализа LTV
 * 
 * @package App\Http\Controllers\Api\V2\Analytics
 */
final class MLAnalyticsController extends Controller
{
    private readonly DemandForecastMLService $demandService;
    private readonly PriceSuggestionMLService $priceService;
    private readonly CustomerLifetimeValueMLService $ltv;

    public function __construct(
        DemandForecastMLService $demandService,
        PriceSuggestionMLService $priceService,
        CustomerLifetimeValueMLService $ltvService
    ) {
        $this->demandService = $demandService;
        $this->priceService = $priceService;
        $this->ltvService = $ltvService;
    }

    /**
     * GET /api/v2/ml-analytics/demand-forecast
     * Прогноз спроса на товар/услугу
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function demandForecast(Request $request): JsonResponse
    {
        $correlationId = $request->get('correlation_id', Str::uuid()->toString());

        try {
            $itemId = $request->integer('item_id');
            $daysAhead = $request->integer('days_ahead', 30);
            $daysAhead = min($daysAhead, 90); // Макс 90 дней

            $dateFrom = now();
            $dateTo = now()->addDays($daysAhead);

            $forecast = $this->demandService->forecastForItem($itemId, $dateFrom, $dateTo);

            Log::channel('audit')->info('Demand forecast generated', [
                'item_id' => $itemId,
                'correlation_id' => $correlationId,
                'days_ahead' => $daysAhead,
                'predicted_demand' => $forecast['predicted_demand'],
                'confidence' => $forecast['confidence_score'],
                'timestamp' => now()->toIso8601String()
            ]);

            return response()->json([
                'correlation_id' => $correlationId,
                'data' => $forecast,
                'timestamp' => now()->toIso8601String()
            ]);

        } catch (\Throwable $e) {
            Log::channel('analytics_errors')->error('Demand forecast failed', [
                'correlation_id' => $correlationId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'correlation_id' => $correlationId,
                'error' => 'Forecast generation failed'
            ], 500);
        }
    }

    /**
     * POST /api/v2/ml-analytics/price-suggestion
     * Рекомендация по цене на основе спроса, конкуренции, сезонности
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function priceSuggestion(Request $request): JsonResponse
    {
        $correlationId = $request->get('correlation_id', Str::uuid()->toString());

        try {
            $productId = $request->integer('product_id');
            $tenantId = auth()->user()->tenant_id;

            $priceRecommendation = $this->priceService->getSuggestedPrice($productId, $tenantId);

            Log::channel('audit')->info('Price suggestion generated', [
                'product_id' => $productId,
                'correlation_id' => $correlationId,
                'suggested_price' => $priceRecommendation['suggested_price'],
                'current_price' => $priceRecommendation['current_price'],
                'confidence' => $priceRecommendation['confidence'],
                'timestamp' => now()->toIso8601String()
            ]);

            return response()->json([
                'correlation_id' => $correlationId,
                'data' => $priceRecommendation,
                'timestamp' => now()->toIso8601String()
            ]);

        } catch (\Throwable $e) {
            Log::channel('analytics_errors')->error('Price suggestion failed', [
                'correlation_id' => $correlationId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'correlation_id' => $correlationId,
                'error' => 'Price suggestion failed'
            ], 500);
        }
    }

    /**
     * GET /api/v2/ml-analytics/ltv
     * Получает LTV текущего пользователя и риск оттока (churn)
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function getUserLTV(Request $request): JsonResponse
    {
        $correlationId = $request->get('correlation_id', Str::uuid()->toString());

        try {
            $userId = auth()->id();

            $ltv = $this->ltvService->calculateUserLTV($userId);
            $churn = $this->ltvService->predictChurnProbability($userId);

            Log::channel('audit')->info('User LTV and churn calculated', [
                'user_id' => $userId,
                'correlation_id' => $correlationId,
                'ltv' => $ltv,
                'churn_probability' => $churn['churn_probability'],
                'timestamp' => now()->toIso8601String()
            ]);

            return response()->json([
                'correlation_id' => $correlationId,
                'data' => [
                    'lifetime_value' => round($ltv, 2),
                    'churn' => $churn,
                ]
            ]);

        } catch (\Throwable $e) {
            Log::channel('analytics_errors')->error('LTV calculation failed', [
                'correlation_id' => $correlationId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'correlation_id' => $correlationId,
                'error' => 'LTV calculation failed'
            ], 500);
        }
    }

    /**
     * GET /api/v2/ml-analytics/segments
     * Получает сегментацию клиентов по LTV и риску
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function getCustomerSegments(Request $request): JsonResponse
    {
        $correlationId = $request->get('correlation_id', Str::uuid()->toString());

        try {
            $tenantId = auth()->user()->tenant_id;

            $segments = $this->ltvService->segmentCustomersByValue($tenantId);

            return response()->json([
                'correlation_id' => $correlationId,
                'data' => [
                    'high_value_count' => count($segments['high_value']),
                    'medium_value_count' => count($segments['medium_value']),
                    'low_value_count' => count($segments['low_value']),
                    'at_risk_count' => count($segments['at_risk']),
                    'dormant_count' => count($segments['dormant']),
                    'new_count' => count($segments['new']),
                    'segments' => $segments,
                ]
            ]);

        } catch (\Throwable $e) {
            Log::channel('analytics_errors')->error('Customer segmentation failed', [
                'correlation_id' => $correlationId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'correlation_id' => $correlationId,
                'error' => 'Segmentation failed'
            ], 500);
        }
    }
}
