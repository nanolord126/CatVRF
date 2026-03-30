<?php declare(strict_types=1);

namespace App\Http\Controllers\Api\Tickets;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class TicketAIApiController extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    /**
         * Конструктор с зависимостями.
         */
        public function __construct(
            private readonly TicketAIService $aiService
        ) {}
        /**
         * Получение персональных рекомендаций эвентов.
         */
        public function suggestEvents(Request $request): JsonResponse
        {
            $correlationId = $request->header('X-Correlation-ID', (string) Str::uuid());
            try {
                $userId = auth()->id() ?? 1;
                $context = [
                    'lat' => $request->query('lat'),
                    'lon' => $request->query('lon'),
                    'correlation_id' => $correlationId
                ];
                $events = $this->aiService->suggestEventsForUser($userId, $context);
                return response()->json([
                    'success' => true,
                    'correlation_id' => $correlationId,
                    'data' => [
                        'events' => $events->map(fn ($e) => [
                            'uuid' => $e->uuid,
                            'title' => $e->title,
                            'category' => $e->category_label,
                            'venue' => $e->venue->name,
                            'start_at' => $e->start_at->toIso8601String(),
                            'has_available' => $e->hasAvailableTickets()
                        ]),
                    ]
                ]);
            } catch (\Throwable $e) {
                Log::channel('audit')->error('AI suggestion API failure', [
                    'error' => $e->getMessage(),
                    'correlation_id' => $correlationId
                ]);
                return response()->json([
                    'success' => false,
                    'correlation_id' => $correlationId,
                    'error' => 'Ошибка при получении рекомендаций'
                ], 500);
            }
        }
        /**
         * Получение прогноза спроса на эвент (только для бизнеса).
         */
        public function forecastDemand(int $eventId, Request $request): JsonResponse
        {
            $correlationId = $request->header('X-Correlation-ID', (string) Str::uuid());
            try {
                // Проверка прав (Бизнес / Тенант)
                $this->authorize('view_forecast', \App\Domains\Tickets\Models\Event::class);
                $prediction = $this->aiService->predictEventDemand($eventId);
                return response()->json([
                    'success' => true,
                    'correlation_id' => $correlationId,
                    'data' => $prediction
                ]);
            } catch (\Throwable $e) {
                Log::channel('recommend')->error('Forecast API failure', [
                    'event_id' => $eventId,
                    'error' => $e->getMessage(),
                    'correlation_id' => $correlationId
                ]);
                return response()->json([
                    'success' => false,
                    'correlation_id' => $correlationId,
                    'error' => 'Не удалось получить прогноз спроса'
                ], 403);
            }
        }
        /**
         * AI-конструктор схемы зала по требованиям.
         */
        public function generateSeatMap(Request $request): JsonResponse
        {
            $correlationId = (string) Str::uuid();
            try {
                $requirements = $request->validate([
                    'venue_name' => 'required|string',
                    'description' => 'required|string',
                    'total_capacity' => 'required|integer|max:100000',
                    'is_standing_only' => 'boolean'
                ]);
                $result = $this->aiService->designSeatMapLayout($requirements);
                return response()->json([
                    'success' => true,
                    'correlation_id' => $correlationId,
                    'data' => $result['layout']
                ]);
            } catch (\Throwable $e) {
                return response()->json([
                    'success' => false,
                    'correlation_id' => $correlationId,
                    'error' => $e->getMessage()
                ], 422);
            }
        }
}
