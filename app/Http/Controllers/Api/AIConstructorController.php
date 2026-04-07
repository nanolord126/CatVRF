<?php declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Log\LogManager;
use Illuminate\Database\DatabaseManager;
use Illuminate\Contracts\Routing\ResponseFactory;

final class AIConstructorController extends Controller
{

    public function __construct(
            private readonly AIConstructorService $constructorService,
            private readonly LogManager $logger,
            private readonly DatabaseManager $db,
            private readonly ResponseFactory $response,
    ) {}
        /**
         * Запустить конструктор
         */
        public function run(AIConstructorRequest $request): JsonResponse
        {
            try {
                $result = $this->db->transaction(fn () => $this->constructorService->run(
                    user: $request->user(),
                    type: $request->validated('type'),
                    photo: $request->file('photo'),
                    params: $request->validated('params', []),
                ));
                return $this->response->json([
                    'success' => true,
                    'correlation_id' => $result['correlation_id'],
                    'construction' => [
                        'id' => $result['construction']->id,
                        'uuid' => $result['construction']->uuid,
                        'type' => $result['construction']->type,
                        'photo_url' => $result['construction']->getPhotoUrl(),
                        'confidence' => $result['confidence'],
                    ],
                    'result' => $result['result'],
                    'taste_used' => $result['taste_used'],
                ], 201);
            } catch (\Throwable $e) {
                $this->logger->channel('audit')->error('AI Constructor API failed', [
                    'user_id' => $request->user()?->id,
                    'error' => $e->getMessage(),
                ]);
                return $this->response->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                ], 500);
            }
        }
        /**
         * Получить конструкцию по ID
         */
        public function show(AIConstruction $construction): JsonResponse
        {
            $this->authorize('view', $construction);
            return $this->response->json([
                'success' => true,
                'construction' => $this->formatConstruction($construction),
            ]);
        }
        /**
         * Получить сохранённые конструкции
         */
        public function saved(Request $request): JsonResponse
        {
            $constructions = $this->constructorService->getSavedConstructions(
                $request->user(),
                $request->query('type'),
                $request->query('limit', 20),
            );
            return $this->response->json([
                'success' => true,
                'data' => $constructions,
                'count' => \count($constructions),
            ]);
        }
        /**
         * Получить статистику
         */
        public function statistics(Request $request): JsonResponse
        {
            $stats = $this->constructorService->getStatistics($request->user());
            return $this->response->json([
                'success' => true,
                'statistics' => $stats,
            ]);
        }
        /**
         * Сохранить конструкцию в избранное
         */
        public function save(AIConstruction $construction): JsonResponse
        {
            $this->authorize('update', $construction);
            $construction->markAsSaved();
            return $this->response->json([
                'success' => true,
                'message' => 'Construction saved',
            ]);
        }
        /**
         * Удалить из избранного
         */
        public function unsave(AIConstruction $construction): JsonResponse
        {
            $this->authorize('update', $construction);
            $construction->unmarkAsSaved();
            return $this->response->json([
                'success' => true,
                'message' => 'Construction removed from saved',
            ]);
        }
        /**
         * Добавить отзыв
         */
        public function review(Request $request, AIConstruction $construction): JsonResponse
        {
            $this->authorize('update', $construction);
            $validated = $request->validate([
                'rating' => 'required|integer|min:1|max:5',
                'feedback' => 'nullable|string|max:500',
            ]);
            $construction->addFeedback(
                $validated['rating'],
                $validated['feedback'] ?? null,
            );
            return $this->response->json([
                'success' => true,
                'message' => 'Review added',
            ]);
        }
        /**
         * Удалить конструкцию
         */
        public function destroy(AIConstruction $construction): JsonResponse
        {
            $this->authorize('delete', $construction);
            $this->constructorService->deleteConstruction($construction);
            return $this->response->json([
                'success' => true,
                'message' => 'Construction deleted',
            ]);
        }
        /**
         * Записать покупку товаров из конструкции
         */
        public function recordPurchase(Request $request, AIConstruction $construction): JsonResponse
        {
            $this->authorize('update', $construction);
            $validated = $request->validate([
                'items_count' => 'required|integer|min:1',
                'total_amount' => 'required|integer|min:1',
            ]);
            $construction->recordPurchase(
                $validated['items_count'],
                $validated['total_amount'],
            );
            return $this->response->json([
                'success' => true,
                'message' => 'Purchase recorded',
                'conversion_rate' => $construction->getConversionRate(),
            ]);
        }
        /**
         * Форматировать конструкцию для ответа
         */
        private function formatConstruction(AIConstruction $construction): array
        {
            return [
                'id' => $construction->id,
                'uuid' => $construction->uuid,
                'type' => $construction->type,
                'photo_url' => $construction->getPhotoUrl(),
                'confidence' => $construction->confidence_score,
                'confidence_breakdown' => $construction->confidence_breakdown,
                'construction_data' => $construction->construction_data,
                'recommended_items' => $construction->recommended_items,
                'items_count' => \count($construction->recommended_items ?? []),
                'taste_used' => [
                    'explicit' => $construction->explicit_preferences_used,
                    'implicit' => $construction->implicit_preferences_used,
                ],
                'saved' => $construction->saved,
                'saved_at' => $construction->saved_at,
                'rating' => $construction->rating,
                'feedback' => $construction->feedback,
                'view_count' => $construction->view_count,
                'items_added_to_cart' => $construction->items_added_to_cart,
                'items_purchased' => $construction->items_purchased,
                'purchase_total' => $construction->purchase_total,
                'conversion_rate' => $construction->getConversionRate(),
                'created_at' => $construction->created_at,
            ];
        }
}
