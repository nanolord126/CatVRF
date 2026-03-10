<?php

namespace App\Domains\Taxi\Http\Controllers;

use App\Domains\Taxi\Models\TaxiRide;
use App\Domains\Taxi\Services\TaxiMainService;
use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * TaxiRideController - API контроллер для управления поездками (Production 2026).
 *
 * Предоставляет REST endpoints для:
 * - Создания новых поездок (booking)
 * - Просмотра статуса поездки
 * - Отмены поездок
 * - Получения статистики по поездкам
 *
 * @package App\Domains\Taxi\Http\Controllers
 */
class TaxiRideController extends Controller
{
    use AuthorizesRequests;

    private string $correlationId;

    public function __construct(
        private TaxiMainService $taxiService
    ) {
        $this->correlationId = request()->header('X-Correlation-ID', \Str::uuid()->toString());
        $this->middleware('auth:sanctum');
    }

    /**
     * GET /api/taxi/rides
     * Получить список всех поездок (с пагинацией и фильтрацией).
     *
     * Query параметры:
     * - status: pending|accepted|completed|cancelled
     * - date_from: YYYY-MM-DD
     * - date_to: YYYY-MM-DD
     * - driver_id: ID водителя (для фильтра)
     * - limit: количество записей (по умолчанию 15)
     * - page: номер страницы
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $this->authorize('viewAny', TaxiRide::class);

            $query = TaxiRide::where('tenant_id', auth()->user()->tenant_id);

            // Фильтр по статусу
            if ($request->filled('status')) {
                $query->where('status', $request->string('status'));
            }

            // Фильтр по дате
            if ($request->filled('date_from')) {
                $query->whereDate('created_at', '>=', $request->date('date_from'));
            }
            if ($request->filled('date_to')) {
                $query->whereDate('created_at', '<=', $request->date('date_to'));
            }

            // Фильтр по водителю
            if ($request->filled('driver_id')) {
                $query->where('taxi_driver_id', $request->integer('driver_id'));
            }

            // Сортировка и пагинация
            $limit = $request->integer('limit', 15);
            $rides = $query->orderByDesc('created_at')
                ->paginate($limit);

            Log::info('Fetched taxi rides list', [
                'count' => $rides->count(),
                'user_id' => auth()->id(),
                'correlation_id' => $this->correlationId,
            ]);

            return response()->json([
                'success' => true,
                'data' => $rides->items(),
                'pagination' => [
                    'total' => $rides->total(),
                    'per_page' => $rides->perPage(),
                    'current_page' => $rides->currentPage(),
                    'last_page' => $rides->lastPage(),
                ],
                'correlation_id' => $this->correlationId,
            ]);
        } catch (Throwable $e) {
            Log::error('Failed to fetch taxi rides', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
                'correlation_id' => $this->correlationId,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Ошибка при получении списка поездок',
                'correlation_id' => $this->correlationId,
            ], 500);
        }
    }

    /**
     * GET /api/taxi/rides/{id}
     * Получить детали конкретной поездки.
     */
    public function show(TaxiRide $ride): JsonResponse
    {
        try {
            $this->authorize('view', $ride);

            Log::info('Fetched taxi ride details', [
                'ride_id' => $ride->id,
                'user_id' => auth()->id(),
                'correlation_id' => $this->correlationId,
            ]);

            return response()->json([
                'success' => true,
                'data' => $ride->load(['taxiDriver', 'taxiVehicle', 'taxiShift']),
                'correlation_id' => $this->correlationId,
            ]);
        } catch (Throwable $e) {
            Log::error('Failed to fetch taxi ride', [
                'ride_id' => $ride->id ?? 'unknown',
                'error' => $e->getMessage(),
                'correlation_id' => $this->correlationId,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Поездка не найдена',
                'correlation_id' => $this->correlationId,
            ], 404);
        }
    }

    /**
     * POST /api/taxi/rides
     * Создать новую поездку (booking).
     *
     * Request body:
     * {
     *   "pickup_latitude": 55.7558,
     *   "pickup_longitude": 37.6173,
     *   "dropoff_latitude": 55.7505,
     *   "dropoff_longitude": 37.6175,
     *   "vehicle_class": "economy|comfort|business",
     *   "scheduled_for": "2026-03-10 15:30:00" (опционально)
     * }
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $this->authorize('create', TaxiRide::class);

            $validated = $request->validate([
                'pickup_latitude' => 'required|numeric|between:-90,90',
                'pickup_longitude' => 'required|numeric|between:-180,180',
                'dropoff_latitude' => 'required|numeric|between:-90,90',
                'dropoff_longitude' => 'required|numeric|between:-180,180',
                'vehicle_class' => 'required|in:economy,comfort,business,xl',
                'scheduled_for' => 'nullable|date_format:Y-m-d H:i:s|after_or_equal:now',
            ]);

            $ride = $this->taxiService->createRide([
                'pickup_lat' => $validated['pickup_latitude'],
                'pickup_lng' => $validated['pickup_longitude'],
                'dropoff_lat' => $validated['dropoff_latitude'],
                'dropoff_lng' => $validated['dropoff_longitude'],
                'vehicle_class' => $validated['vehicle_class'],
                'scheduled_for' => $validated['scheduled_for'] ?? now(),
                'tenant_id' => auth()->user()->tenant_id,
                'correlation_id' => $this->correlationId,
            ]);

            Log::info('Created taxi ride', [
                'ride_id' => $ride->id,
                'user_id' => auth()->id(),
                'correlation_id' => $this->correlationId,
            ]);

            return response()->json([
                'success' => true,
                'data' => $ride,
                'message' => 'Поездка создана успешно',
                'correlation_id' => $this->correlationId,
            ], 201);
        } catch (Throwable $e) {
            Log::error('Failed to create taxi ride', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
                'correlation_id' => $this->correlationId,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Ошибка при создании поездки: ' . $e->getMessage(),
                'correlation_id' => $this->correlationId,
            ], 422);
        }
    }

    /**
     * PATCH /api/taxi/rides/{id}
     * Обновить детали поездки (может быть использовано для изменения параметров).
     */
    public function update(Request $request, TaxiRide $ride): JsonResponse
    {
        try {
            $this->authorize('update', $ride);

            $validated = $request->validate([
                'vehicle_class' => 'sometimes|in:economy,comfort,business,xl',
                'notes' => 'sometimes|string|max:500',
            ]);

            $ride->update($validated);

            Log::info('Updated taxi ride', [
                'ride_id' => $ride->id,
                'user_id' => auth()->id(),
                'correlation_id' => $this->correlationId,
            ]);

            return response()->json([
                'success' => true,
                'data' => $ride,
                'message' => 'Поездка обновлена успешно',
                'correlation_id' => $this->correlationId,
            ]);
        } catch (Throwable $e) {
            Log::error('Failed to update taxi ride', [
                'ride_id' => $ride->id,
                'error' => $e->getMessage(),
                'correlation_id' => $this->correlationId,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Ошибка при обновлении поездки',
                'correlation_id' => $this->correlationId,
            ], 422);
        }
    }

    /**
     * DELETE /api/taxi/rides/{id}
     * Отмена/удаление поездки.
     *
     * Request body:
     * {
     *   "reason": "changed_mind|found_alternative|driver_late"
     * }
     */
    public function destroy(Request $request, TaxiRide $ride): JsonResponse
    {
        try {
            $this->authorize('cancel', $ride);

            $validated = $request->validate([
                'reason' => 'required|string|max:255',
            ]);

            $ride->cancel($validated['reason']);

            Log::info('Cancelled taxi ride', [
                'ride_id' => $ride->id,
                'reason' => $validated['reason'],
                'user_id' => auth()->id(),
                'correlation_id' => $this->correlationId,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Поездка отменена',
                'correlation_id' => $this->correlationId,
            ]);
        } catch (Throwable $e) {
            Log::error('Failed to cancel taxi ride', [
                'ride_id' => $ride->id,
                'error' => $e->getMessage(),
                'correlation_id' => $this->correlationId,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Ошибка при отмене поездки',
                'correlation_id' => $this->correlationId,
            ], 422);
        }
    }

    /**
     * POST /api/taxi/rides/{id}/complete
     * Завершить поездку (вычислить финальную стоимость, обновить статистику).
     *
     * Request body:
     * {
     *   "distance_km": 12.5,
     *   "duration_minutes": 35
     * }
     */
    public function complete(Request $request, TaxiRide $ride): JsonResponse
    {
        try {
            $this->authorize('complete', $ride);

            $validated = $request->validate([
                'distance_km' => 'required|numeric|min:0.1',
                'duration_minutes' => 'required|integer|min:1',
            ]);

            $ride->complete($validated['distance_km'], $validated['duration_minutes']);

            Log::info('Completed taxi ride', [
                'ride_id' => $ride->id,
                'distance_km' => $validated['distance_km'],
                'duration_minutes' => $validated['duration_minutes'],
                'correlation_id' => $this->correlationId,
            ]);

            return response()->json([
                'success' => true,
                'data' => $ride->refresh(),
                'message' => 'Поездка завершена',
                'correlation_id' => $this->correlationId,
            ]);
        } catch (Throwable $e) {
            Log::error('Failed to complete taxi ride', [
                'ride_id' => $ride->id,
                'error' => $e->getMessage(),
                'correlation_id' => $this->correlationId,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Ошибка при завершении поездки',
                'correlation_id' => $this->correlationId,
            ], 422);
        }
    }

    /**
     * GET /api/taxi/rides/statistics
     * Получить статистику по поездкам (отдельный эндпоинт для аналитики).
     *
     * Query параметры:
     * - days: количество дней для анализа (по умолчанию 30)
     * - driver_id: ID водителя (опционально)
     */
    public function statistics(Request $request): JsonResponse
    {
        try {
            $this->authorize('viewAny', TaxiRide::class);

            $days = $request->integer('days', 30);
            $driverId = $request->integer('driver_id');

            $stats = $this->taxiService->getRevenueStats(
                auth()->user()->tenant_id,
                -$days,
                $driverId
            );

            Log::info('Fetched taxi statistics', [
                'days' => $days,
                'correlation_id' => $this->correlationId,
            ]);

            return response()->json([
                'success' => true,
                'data' => $stats,
                'correlation_id' => $this->correlationId,
            ]);
        } catch (Throwable $e) {
            Log::error('Failed to fetch taxi statistics', [
                'error' => $e->getMessage(),
                'correlation_id' => $this->correlationId,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Ошибка при получении статистики',
                'correlation_id' => $this->correlationId,
            ], 500);
        }
    }
}
