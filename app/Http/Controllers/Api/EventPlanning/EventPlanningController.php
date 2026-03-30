<?php declare(strict_types=1);

namespace App\Http\Controllers\Api\EventPlanning;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class EventPlanningController extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    public function __construct(
            private EventPlanningService $eventService,
        ) {}
        /**
         * Создание события (B2C/B2B).
         */
        public function store(CreateEventRequest $request): JsonResponse
        {
            $correlationId = $request->header('X-Correlation-ID', (string) \Illuminate\Support\Str::uuid());
            try {
                Log::channel('audit')->info("API-Request: Store Event starting", [
                    'correlation_id' => $correlationId,
                    'user_id' => $request->user()?->id,
                ]);
                $event = $this->eventService->createEventProject($request->validated(), $correlationId);
                return response()->json([
                    'success' => true,
                    'uuid' => $event->uuid,
                    'status' => $event->status,
                    'budget_plan' => $event->total_budget_kopecks / 100,
                    'prepayment_required' => $event->prepayment_kopecks / 100,
                    'ai_overview' => $event->ai_plan['overview'] ?? [],
                    'correlation_id' => $correlationId,
                ], 201);
            } catch (Exception $e) {
                Log::channel('audit')->error("API-Error: Store Event failed", [
                    'correlation_id' => $correlationId,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
                return response()->json([
                    'success' => false,
                    'message' => 'Ошибка при создании плана события. Попробуйте еще раз позже.',
                    'correlation_id' => $correlationId,
                ], 500);
            }
        }
        /**
         * Список событий пользователя.
         */
        public function index(Request $request): JsonResponse
        {
            $correlationId = $request->header('X-Correlation-ID', (string) \Illuminate\Support\Str::uuid());
            try {
                $events = Event::where('client_id', $request->user()?->id)
                    ->with(['vendors'])
                    ->orderBy('created_at', 'desc')
                    ->paginate(15);
                return response()->json([
                    'success' => true,
                    'data' => $events,
                    'correlation_id' => $correlationId,
                ]);
            } catch (Exception $e) {
                return response()->json([
                    'success' => false,
                    'message' => 'Не удалось загрузить ваши события.',
                    'correlation_id' => $correlationId,
                ], 500);
            }
        }
        /**
         * Детали события.
         */
        public function show(Event $event, Request $request): JsonResponse
        {
            $correlationId = $request->header('X-Correlation-ID', (string) \Illuminate\Support\Str::uuid());
            try {
                // Eager loading
                $event->load(['vendors', 'budgetItems']);
                return response()->json([
                    'success' => true,
                    'data' => $event,
                    'correlation_id' => $correlationId,
                ]);
            } catch (Exception $e) {
                return response()->json(['success' => false, 'message' => 'Событие не найдено.'], 404);
            }
        }
        /**
         * Отмена события пользователем.
         */
        public function cancel(Event $event, Request $request): JsonResponse
        {
            $correlationId = $request->header('X-Correlation-ID', (string) \Illuminate\Support\Str::uuid());
            try {
                $this->eventService->cancelEvent($event, $request->input('reason', 'Запрос пользователя'), $correlationId);
                return response()->json([
                    'success' => true,
                    'message' => 'Событие успешно отменено. Штраф списан согласно правилам политики отмены.',
                    'correlation_id' => $correlationId,
                ]);
            } catch (Exception $e) {
                return response()->json([
                    'success' => false,
                    'message' => 'Ошибка при отмене события.',
                    'correlation_id' => $correlationId,
                ], 500);
            }
        }
}
