<?php declare(strict_types=1);

namespace App\Http\Controllers\Api\V2\Analytics;

use App\Http\Controllers\Controller;
use Illuminate\Log\LogManager;
use Illuminate\Database\DatabaseManager;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Contracts\Routing\ResponseFactory;

final class RecommendationController extends Controller
{

    private readonly RecommendationMLService $recommendationService;
        public function __construct(RecommendationMLService $recommendationService,
        private readonly LogManager $logger,
        private readonly DatabaseManager $db,
        private readonly Guard $guard,
        private readonly ResponseFactory $response,
    )
        {
            $this->recommendationService = $recommendationService;
            // PRODUCTION-READY 2026 CANON: Middleware для ML Recommendations
            $this->middleware('auth:sanctum')->only(['getForMe', 'getCrossVertical']); // Только для авторизованных
             // 500 запросов/час
            $this->middleware('tenant', ['only' => ['getForMe', 'getCrossVertical']]); // Tenant scoping
            $this->middleware('fraud-check', ['only' => ['rateRecommendation']]); // Проверка перед рейтингом
        }
        /**
         * GET /api/v2/recommendations/for-me
         * Получает персональные рекомендации для текущего пользователя
         *
         * @param Request $request
         * @return JsonResponse
         */
        public function getForMe(Request $request): JsonResponse
        {
            $correlationId = $request->get('correlation_id', Str::uuid()->toString());
            try {
                $vertical = $request->get('vertical'); // Опционально: beauty, food, auto, etc.
                $context = [
                    'geo_hash' => $request->get('geo_hash'),
                    'device_type' => $request->get('device_type', 'mobile'),
                ];
                $recommendations = $this->recommendationService->getForUser(
                    userId: $this->guard->id(),
                    vertical: $vertical,
                    context: $context
                );
                $this->logger->channel('audit')->info('Recommendations fetched', [
                    'user_id' => $this->guard->id(),
                    'correlation_id' => $correlationId,
                    'vertical' => $vertical,
                    'count' => $recommendations->count(),
                    'timestamp' => now()->toIso8601String()
                ]);
                return $this->response->json([
                    'correlation_id' => $correlationId,
                    'data' => [
                        'count' => $recommendations->count(),
                        'recommendations' => $recommendations->toArray(),
                    ],
                    'timestamp' => now()->toIso8601String()
                ]);
            } catch (\Throwable $e) {
                $this->logger->channel('analytics_errors')->error('Failed to get recommendations', [
                    'user_id' => $this->guard->id(),
                    'correlation_id' => $correlationId,
                    'error' => $e->getMessage()
                ]);
                return $this->response->json([
                    'correlation_id' => $correlationId,
                    'error' => 'Failed to generate recommendations'
                ], 500);
            }
        }
        /**
         * GET /api/v2/recommendations/cross-vertical
         * Получает кросс-вертикальные рекомендации
         * Пример: после записи на маникюр → рекомендация салона красоты рядом
         *
         * @param Request $request
         * @return JsonResponse
         */
        public function getCrossVertical(Request $request): JsonResponse
        {
            $correlationId = $request->get('correlation_id', Str::uuid()->toString());
            try {
                $currentVertical = $request->get('current_vertical', 'beauty');
                // Получаем рекомендации из других вертикалей
                $recommendations = collect();
                $verticals = ['food', 'auto', 'realestate', 'hotels'];
                foreach ($verticals as $vertical) {
                    if ($vertical !== $currentVertical) {
                        $recs = $this->recommendationService->getForUser(
                            userId: $this->guard->id(),
                            vertical: $vertical
                        );
                        $recommendations = $recommendations->merge($recs);
                    }
                }
                return $this->response->json([
                    'correlation_id' => $correlationId,
                    'data' => [
                        'count' => $recommendations->count(),
                        'recommendations' => $recommendations->take(10)->toArray(),
                    ]
                ]);
            } catch (\Throwable $e) {
                $this->logger->channel('analytics_errors')->error('Failed to get cross-vertical recommendations', [
                    'correlation_id' => $correlationId,
                    'error' => $e->getMessage()
                ]);
                return $this->response->json([
                    'correlation_id' => $correlationId,
                    'error' => 'Failed to generate cross-vertical recommendations'
                ], 500);
            }
        }
        /**
         * POST /api/v2/recommendations/track-click
         * Отслеживает клики по рекомендациям для аналитики и переобучения модели
         *
         * @param Request $request
         * @return JsonResponse
         */
        public function trackClick(Request $request): JsonResponse
        {
            $correlationId = $request->get('correlation_id', Str::uuid()->toString());
            try {
                $this->db->table('recommendation_logs')->insert([
                    'user_id' => $this->guard->id(),
                    'tenant_id' => $this->guard->user()->tenant_id,
                    'recommended_item_id' => $request->get('item_id'),
                    'source' => $request->get('source'), // behavior, geo, embedding, etc.
                    'clicked_at' => now(),
                    'correlation_id' => $correlationId,
                ]);
                return $this->response->json([
                    'correlation_id' => $correlationId,
                    'data' => ['tracked' => true]
                ]);
            } catch (\Throwable $e) {
                $this->logger->channel('analytics_errors')->error('Failed to track recommendation click', [
                    'correlation_id' => $correlationId,
                    'error' => $e->getMessage()
                ]);
                return $this->response->json([
                    'correlation_id' => $correlationId,
                    'error' => 'Failed to track click'
                ], 500);
            }
        }
}
