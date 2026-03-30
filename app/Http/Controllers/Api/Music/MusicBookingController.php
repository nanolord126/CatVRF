<?php declare(strict_types=1);

namespace App\Http\Controllers\Api\Music;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class MusicBookingController extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    /**
         * Create a new controller instance.
         */
        public function __construct(
            private readonly MusicBookingService $bookingService
        ) {}
        /**
         * Display a listing of bookings for current tenant.
         */
        public function index(): JsonResponse
        {
            $correlationId = (string) Str::uuid();
            try {
                $bookings = $this->bookingService->listBookings(tenant()->id);
                return response()->json([
                    'success' => true,
                    'data' => $bookings,
                    'correlation_id' => $correlationId,
                ]);
            } catch (\Throwable $e) {
                Log::channel('audit')->error('Failed to list music bookings', [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                    'correlation_id' => $correlationId,
                ]);
                return response()->json([
                    'success' => false,
                    'message' => 'Не удалось получить список бронирований.',
                    'correlation_id' => $correlationId,
                ], 500);
            }
        }
        /**
         * Create a new booking for a studio or lesson.
         */
        public function store(MusicBookingRequest $request): JsonResponse
        {
            $correlationId = $request->input('correlation_id', (string) Str::uuid());
            try {
                $booking = $this->bookingService->createBooking(
                    $request->validated(),
                    tenant()->id,
                    $correlationId
                );
                Log::channel('audit')->info('New music booking created via API', [
                    'booking_id' => $booking->id,
                    'bookable_type' => $booking->bookable_type,
                    'correlation_id' => $correlationId,
                ]);
                return response()->json([
                    'success' => true,
                    'data' => $booking,
                    'correlation_id' => $correlationId,
                ], 201);
            } catch (\Throwable $e) {
                Log::channel('audit')->error('Failed to create music booking', [
                    'error' => $e->getMessage(),
                    'correlation_id' => $correlationId,
                ]);
                return response()->json([
                    'success' => false,
                    'message' => 'Ошибка при бронировании: ' . $e->getMessage(),
                    'correlation_id' => $correlationId,
                ], 422);
            }
        }
        /**
         * Show detailed information for a specific booking.
         */
        public function show(int $id): JsonResponse
        {
            $correlationId = (string) Str::uuid();
            try {
                $booking = $this->bookingService->getBookingWithDetails($id);
                return response()->json([
                    'success' => true,
                    'data' => $booking,
                    'correlation_id' => $correlationId,
                ]);
            } catch (\Throwable $e) {
                return response()->json([
                    'success' => false,
                    'message' => 'Бронирование не найдено.',
                    'correlation_id' => $correlationId,
                ], 404);
            }
        }
        /**
         * Cancel the specified booking.
         */
        public function cancel(int $id): JsonResponse
        {
            $correlationId = (string) Str::uuid();
            try {
                $this->bookingService->cancelBooking($id, $correlationId);
                return response()->json([
                    'success' => true,
                    'message' => 'Бронирование успешно отменено.',
                    'correlation_id' => $correlationId,
                ]);
            } catch (\Throwable $e) {
                Log::channel('audit')->error('Failed to cancel music booking', [
                    'booking_id' => $id,
                    'error' => $e->getMessage(),
                    'correlation_id' => $correlationId,
                ]);
                return response()->json([
                    'success' => false,
                    'message' => 'Ошибка при отмене бронирования: ' . $e->getMessage(),
                    'correlation_id' => $correlationId,
                ], 422);
            }
        }
        /**
         * Update the status of a booking (internal or management tool).
         */
        public function updateStatus(int $id, string $status): JsonResponse
        {
            $correlationId = (string) Str::uuid();
            try {
                $booking = $this->bookingService->updateBookingStatus($id, $status, $correlationId);
                return response()->json([
                    'success' => true,
                    'data' => $booking,
                    'correlation_id' => $correlationId,
                ]);
            } catch (\Throwable $e) {
                return response()->json([
                    'success' => false,
                    'message' => 'Ошибка при обновлении статуса бронирования.',
                    'correlation_id' => $correlationId,
                ], 422);
            }
        }
}
