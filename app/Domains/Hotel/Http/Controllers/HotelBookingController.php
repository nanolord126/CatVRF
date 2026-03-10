<?php

namespace App\Domains\Hotel\Http\Controllers;

use App\Domains\Hotel\Models\{HotelBooking, HotelRoom};
use App\Domains\Hotel\Services\HotelService;
use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * HotelBookingController - API контроллер для управления бронированиями (Production 2026).
 *
 * @package App\Domains\Hotel\Http\Controllers
 */
class HotelBookingController extends Controller
{
    use AuthorizesRequests;

    private string $correlationId;

    public function __construct(
        private HotelService $hotelService
    ) {
        $this->correlationId = request()->header('X-Correlation-ID', \Str::uuid()->toString());
        $this->middleware('auth:sanctum');
    }

    /**
     * GET /api/hotels/{hotelId}/rooms/available
     * Получить доступные номера на определенные даты.
     *
     * Query параметры:
     * - check_in: YYYY-MM-DD (обязательно)
     * - check_out: YYYY-MM-DD (обязательно)
     */
    public function availableRooms(Request $request, int $hotelId): JsonResponse
    {
        try {
            $validated = $request->validate([
                'check_in' => 'required|date_format:Y-m-d',
                'check_out' => 'required|date_format:Y-m-d|after:check_in',
            ]);

            $rooms = $this->hotelService->getAvailableRooms(
                $hotelId,
                $validated['check_in'],
                $validated['check_out']
            );

            Log::info('Fetched available hotel rooms', [
                'hotel_id' => $hotelId,
                'check_in' => $validated['check_in'],
                'check_out' => $validated['check_out'],
                'count' => count($rooms),
                'correlation_id' => $this->correlationId,
            ]);

            return response()->json([
                'success' => true,
                'data' => $rooms,
                'correlation_id' => $this->correlationId,
            ]);
        } catch (Throwable $e) {
            Log::error('Failed to fetch available rooms', [
                'hotel_id' => $hotelId,
                'error' => $e->getMessage(),
                'correlation_id' => $this->correlationId,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Ошибка при получении доступных номеров',
                'correlation_id' => $this->correlationId,
            ], 500);
        }
    }

    /**
     * GET /api/hotel/bookings
     * Получить список бронирований с фильтрацией.
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $this->authorize('viewAny', HotelBooking::class);

            $query = HotelBooking::where('tenant_id', auth()->user()->tenant_id);

            // Фильтр по статусу
            if ($request->filled('status')) {
                $query->where('status', $request->string('status'));
            }

            // Фильтр по отелю
            if ($request->filled('hotel_id')) {
                $query->where('hotel_id', $request->integer('hotel_id'));
            }

            // Для обычных пользователей - только свои бронирования
            if (!auth()->user()->hasAnyRole(['admin', 'tenant-owner', 'manager', 'hotel-staff'])) {
                $query->where('user_id', auth()->id());
            }

            // Фильтр по датам
            if ($request->filled('date_from')) {
                $query->whereDate('check_in_date', '>=', $request->date('date_from'));
            }
            if ($request->filled('date_to')) {
                $query->whereDate('check_out_date', '<=', $request->date('date_to'));
            }

            $limit = $request->integer('limit', 15);
            $bookings = $query->with(['room', 'hotel'])
                ->orderByDesc('created_at')
                ->paginate($limit);

            Log::info('Fetched hotel bookings list', [
                'count' => $bookings->count(),
                'user_id' => auth()->id(),
                'correlation_id' => $this->correlationId,
            ]);

            return response()->json([
                'success' => true,
                'data' => $bookings->items(),
                'pagination' => [
                    'total' => $bookings->total(),
                    'per_page' => $bookings->perPage(),
                    'current_page' => $bookings->currentPage(),
                    'last_page' => $bookings->lastPage(),
                ],
                'correlation_id' => $this->correlationId,
            ]);
        } catch (Throwable $e) {
            Log::error('Failed to fetch hotel bookings', [
                'error' => $e->getMessage(),
                'correlation_id' => $this->correlationId,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Ошибка при получении бронирований',
                'correlation_id' => $this->correlationId,
            ], 500);
        }
    }

    /**
     * GET /api/hotel/bookings/{id}
     * Получить детали конкретного бронирования.
     */
    public function show(HotelBooking $booking): JsonResponse
    {
        try {
            $this->authorize('view', $booking);

            Log::info('Fetched hotel booking details', [
                'booking_id' => $booking->id,
                'correlation_id' => $this->correlationId,
            ]);

            return response()->json([
                'success' => true,
                'data' => $booking->load(['room', 'hotel']),
                'correlation_id' => $this->correlationId,
            ]);
        } catch (Throwable $e) {
            Log::error('Failed to fetch hotel booking', [
                'booking_id' => $booking->id ?? 'unknown',
                'error' => $e->getMessage(),
                'correlation_id' => $this->correlationId,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Бронирование не найдено',
                'correlation_id' => $this->correlationId,
            ], 404);
        }
    }

    /**
     * POST /api/hotel/bookings
     * Создать новое бронирование.
     *
     * Request body:
     * {
     *   "hotel_id": 1,
     *   "room_id": 5,
     *   "check_in_date": "2026-03-15",
     *   "check_out_date": "2026-03-18",
     *   "guest_name": "Иван Петров",
     *   "guest_email": "ivan@example.com",
     *   "guest_phone": "+7 (999) 123-45-67",
     *   "special_requests": "Вид на море"
     * }
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $this->authorize('create', HotelBooking::class);

            $validated = $request->validate([
                'hotel_id' => 'required|integer|exists:hotels,id',
                'room_id' => 'required|integer|exists:hotel_rooms,id',
                'check_in_date' => 'required|date_format:Y-m-d|after_or_equal:today',
                'check_out_date' => 'required|date_format:Y-m-d|after:check_in_date',
                'guest_name' => 'required|string|max:255',
                'guest_email' => 'required|email|max:255',
                'guest_phone' => 'required|string|max:20',
                'special_requests' => 'nullable|string|max:1000',
            ]);

            $booking = $this->hotelService->createBooking([
                'hotel_id' => $validated['hotel_id'],
                'room_id' => $validated['room_id'],
                'check_in_date' => $validated['check_in_date'],
                'check_out_date' => $validated['check_out_date'],
                'guest_name' => $validated['guest_name'],
                'guest_email' => $validated['guest_email'],
                'guest_phone' => $validated['guest_phone'],
                'special_requests' => $validated['special_requests'],
            ]);

            Log::info('Created hotel booking', [
                'booking_id' => $booking->id,
                'user_id' => auth()->id(),
                'correlation_id' => $this->correlationId,
            ]);

            return response()->json([
                'success' => true,
                'data' => $booking,
                'message' => 'Бронирование создано успешно',
                'correlation_id' => $this->correlationId,
            ], 201);
        } catch (Throwable $e) {
            Log::error('Failed to create hotel booking', [
                'error' => $e->getMessage(),
                'correlation_id' => $this->correlationId,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Ошибка при создании бронирования: ' . $e->getMessage(),
                'correlation_id' => $this->correlationId,
            ], 422);
        }
    }

    /**
     * PATCH /api/hotel/bookings/{id}
     * Обновить бронирование.
     */
    public function update(Request $request, HotelBooking $booking): JsonResponse
    {
        try {
            $this->authorize('update', $booking);

            $validated = $request->validate([
                'guest_name' => 'sometimes|string|max:255',
                'guest_email' => 'sometimes|email|max:255',
                'guest_phone' => 'sometimes|string|max:20',
                'special_requests' => 'sometimes|string|max:1000',
            ]);

            $booking->update($validated);

            Log::info('Updated hotel booking', [
                'booking_id' => $booking->id,
                'correlation_id' => $this->correlationId,
            ]);

            return response()->json([
                'success' => true,
                'data' => $booking,
                'message' => 'Бронирование обновлено успешно',
                'correlation_id' => $this->correlationId,
            ]);
        } catch (Throwable $e) {
            Log::error('Failed to update hotel booking', [
                'booking_id' => $booking->id,
                'error' => $e->getMessage(),
                'correlation_id' => $this->correlationId,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Ошибка при обновлении бронирования',
                'correlation_id' => $this->correlationId,
            ], 422);
        }
    }

    /**
     * DELETE /api/hotel/bookings/{id}
     * Отменить бронирование.
     */
    public function destroy(Request $request, HotelBooking $booking): JsonResponse
    {
        try {
            $this->authorize('cancel', $booking);

            $validated = $request->validate([
                'reason' => 'required|string|max:255',
            ]);

            $this->hotelService->cancelBooking($booking, $validated['reason']);

            Log::info('Cancelled hotel booking', [
                'booking_id' => $booking->id,
                'reason' => $validated['reason'],
                'correlation_id' => $this->correlationId,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Бронирование отменено',
                'correlation_id' => $this->correlationId,
            ]);
        } catch (Throwable $e) {
            Log::error('Failed to cancel hotel booking', [
                'booking_id' => $booking->id,
                'error' => $e->getMessage(),
                'correlation_id' => $this->correlationId,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Ошибка при отмене бронирования',
                'correlation_id' => $this->correlationId,
            ], 422);
        }
    }

    /**
     * POST /api/hotel/bookings/{id}/confirm
     * Подтвердить бронирование.
     */
    public function confirm(HotelBooking $booking): JsonResponse
    {
        try {
            $this->authorize('confirm', $booking);

            $this->hotelService->confirmBooking($booking);

            Log::info('Confirmed hotel booking', [
                'booking_id' => $booking->id,
                'correlation_id' => $this->correlationId,
            ]);

            return response()->json([
                'success' => true,
                'data' => $booking->refresh(),
                'message' => 'Бронирование подтверждено',
                'correlation_id' => $this->correlationId,
            ]);
        } catch (Throwable $e) {
            Log::error('Failed to confirm hotel booking', [
                'booking_id' => $booking->id,
                'error' => $e->getMessage(),
                'correlation_id' => $this->correlationId,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Ошибка при подтверждении бронирования',
                'correlation_id' => $this->correlationId,
            ], 422);
        }
    }

    /**
     * POST /api/hotel/bookings/{id}/check-in
     * Выполнить check-in (заезд).
     */
    public function checkIn(HotelBooking $booking): JsonResponse
    {
        try {
            $this->authorize('checkIn', $booking);

            $this->hotelService->checkIn($booking);

            Log::info('Hotel check-in performed', [
                'booking_id' => $booking->id,
                'correlation_id' => $this->correlationId,
            ]);

            return response()->json([
                'success' => true,
                'data' => $booking->refresh(),
                'message' => 'Гость заехал',
                'correlation_id' => $this->correlationId,
            ]);
        } catch (Throwable $e) {
            Log::error('Failed to perform check-in', [
                'booking_id' => $booking->id,
                'error' => $e->getMessage(),
                'correlation_id' => $this->correlationId,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Ошибка при check-in',
                'correlation_id' => $this->correlationId,
            ], 422);
        }
    }

    /**
     * POST /api/hotel/bookings/{id}/check-out
     * Выполнить check-out (выезд).
     */
    public function checkOut(HotelBooking $booking): JsonResponse
    {
        try {
            $this->authorize('checkOut', $booking);

            $this->hotelService->checkOut($booking);

            Log::info('Hotel check-out performed', [
                'booking_id' => $booking->id,
                'correlation_id' => $this->correlationId,
            ]);

            return response()->json([
                'success' => true,
                'data' => $booking->refresh(),
                'message' => 'Гость выехал',
                'correlation_id' => $this->correlationId,
            ]);
        } catch (Throwable $e) {
            Log::error('Failed to perform check-out', [
                'booking_id' => $booking->id,
                'error' => $e->getMessage(),
                'correlation_id' => $this->correlationId,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Ошибка при check-out',
                'correlation_id' => $this->correlationId,
            ], 422);
        }
    }

    /**
     * GET /api/hotel/{hotelId}/statistics
     * Получить статистику по отелю.
     *
     * Query параметры:
     * - days: количество дней для анализа (по умолчанию 30)
     */
    public function statistics(Request $request, int $hotelId): JsonResponse
    {
        try {
            $this->authorize('viewAny', HotelBooking::class);

            $days = $request->integer('days', 30);

            $stats = $this->hotelService->getStatistics($hotelId, -$days);

            Log::info('Fetched hotel statistics', [
                'hotel_id' => $hotelId,
                'days' => $days,
                'correlation_id' => $this->correlationId,
            ]);

            return response()->json([
                'success' => true,
                'data' => $stats,
                'correlation_id' => $this->correlationId,
            ]);
        } catch (Throwable $e) {
            Log::error('Failed to fetch hotel statistics', [
                'hotel_id' => $hotelId,
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
