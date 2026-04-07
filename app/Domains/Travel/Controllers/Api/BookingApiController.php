<?php declare(strict_types=1);

namespace App\Domains\Travel\Controllers\Api;


use Psr\Log\LoggerInterface;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Domains\Travel\DTOs\BookingDto;
use App\Domains\Travel\Services\TravelBookingService as BookingService;
use App\Domains\Travel\Services\TravelFraudService;

final class BookingApiController extends Controller
{


    public function __construct(
            private BookingService $bookingService,
            private TravelFraudService $fraudService, private readonly LoggerInterface $logger
        ) {}

        /**
         * Создать бронирование (API POST /api/v1/travel/bookings)
         */
        public function create(Request $request): JsonResponse
        {
            $correlationId = $request->header('X-Correlation-ID', (string) \Illuminate\Support\Str::uuid());

            // 1. Валидация (Слой 8)
            $validated = $request->validate([
                'bookable_type' => 'required|string|in:trip,excursion',
                'bookable_id' => 'required|integer',
                'slots_count' => 'required|integer|min:1|max:10',
                'idempotency_key' => 'nullable|string'
            ]);

            try {
                // 2. Фрод-контроль и Rate Limit (Слой 6)
                $this->fraudService->validateBooking(
                    userId: (int) $request->user()?->id,
                    bookableId: (int) $request->input('bookable_id'),
                    bookableType: (string) $request->input('bookable_type'),
                    context: array_merge($request->all(), ['correlation_id' => $correlationId])
                );

                // 3. Сборка DTO (Слой 4)
                $dto = BookingDto::fromRequest($request, (int) $request->user()?->id, $correlationId);

                // 4. Бизнес-логика (Слой 3)
                $booking = $this->bookingService->createBooking($dto);

                return new \Illuminate\Http\JsonResponse([
                    'success' => true,
                    'data' => [
                        'booking_id' => $booking->id,
                        'total_price' => $booking->total_price,
                        'status' => $booking->status
                    ],
                    'correlation_id' => $correlationId
                ], 201);

            } catch (\Throwable $e) {
                $this->logger->error('Booking creation failed at API level', [
                    'error' => $e->getMessage(),
                    'correlation_id' => $correlationId
                ]);

                return new \Illuminate\Http\JsonResponse([
                    'success' => false,
                    'message' => $e->getMessage(),
                    'correlation_id' => $correlationId
                ], 400);
            }
        }

        /**
         * Оплата бронирования (API POST /api/v1/travel/bookings/{id}/pay)
         */
        public function pay(int $id, Request $request): JsonResponse
        {
            $correlationId = $request->header('X-Correlation-ID', (string) \Illuminate\Support\Str::uuid());

            try {
                $this->bookingService->payBooking($id, $correlationId);

                return new \Illuminate\Http\JsonResponse([
                    'success' => true,
                    'message' => 'Бронирование успешно оплачено',
                    'correlation_id' => $correlationId
                ]);
            } catch (\Throwable $e) {
                return new \Illuminate\Http\JsonResponse([
                    'success' => false,
                    'message' => $e->getMessage(),
                    'correlation_id' => $correlationId
                ], 400);
            }
        }

        /**
         * Отмена бронирования (API DELETE /api/v1/travel/bookings/{id})
         */
        public function cancel(int $id, Request $request): JsonResponse
        {
            $correlationId = $request->header('X-Correlation-ID', (string) \Illuminate\Support\Str::uuid());
            $reason = $request->input('reason', 'Отменено пользователем через API');

            try {
                $this->bookingService->cancelBooking($id, $reason, $correlationId);

                return new \Illuminate\Http\JsonResponse([
                    'success' => true,
                    'message' => 'Бронирование отменено',
                    'correlation_id' => $correlationId
                ]);
            } catch (\Throwable $e) {
                return new \Illuminate\Http\JsonResponse([
                    'success' => false,
                    'message' => $e->getMessage(),
                    'correlation_id' => $correlationId
                ], 400);
            }
        }
}
