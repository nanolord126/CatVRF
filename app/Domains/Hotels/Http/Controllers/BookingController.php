<?php declare(strict_types=1);

namespace App\Domains\Hotels\Http\Controllers;


use Psr\Log\LoggerInterface;
use App\Http\Controllers\Controller;

final class BookingController extends Controller
{


    public function __construct(
            private readonly BookingService $bookingService,
            private readonly FraudControlService $fraud, private readonly LoggerInterface $logger) {}

        public function index(\Illuminate\Http\Request $request): JsonResponse
        {
            try {
                $this->authorize('viewAny', Booking::class);

                $bookings = Booking::where('guest_id', $request->user()?->id)
                    ->paginate(10);

                return new \Illuminate\Http\JsonResponse([
                    'success' => true,
                    'data' => $bookings,
                ]);
            } catch (\Throwable $e) {
                return new \Illuminate\Http\JsonResponse([
                    'success' => false,
                    'error' => $e->getMessage(),
                ], 500);
            }
        }

        public function show(string $id): JsonResponse
        {
            try {
                $booking = Booking::findOrFail($id);
                $this->authorize('view', $booking);

                return new \Illuminate\Http\JsonResponse([
                    'success' => true,
                    'data' => $booking->load(['hotel', 'roomType']),
                ]);
            } catch (\Throwable $e) {
                return new \Illuminate\Http\JsonResponse([
                    'success' => false,
                    'error' => $e->getMessage(),
                ], 500);
            }
        }

        public function store(\Illuminate\Http\Request $request): JsonResponse
        {
            $correlationId = Str::uuid()->toString();
            $this->fraud->check(userId: $request->user()?->id ?? 0, operationType: 'operation', amount: 0, correlationId: $correlationId ?? '');

            try {
                $this->authorize('create', Booking::class);

                $data = $request->validate([
                    'hotel_id' => 'required|uuid',
                    'room_type_id' => 'required|uuid',
                    'check_in_date' => 'required|date',
                    'check_out_date' => 'required|date|after:check_in_date',
                    'number_of_guests' => 'required|integer|min:1',
                    'special_requests' => 'nullable|string',
                ]);

                $booking = $this->bookingService->createBooking(
                    hotelId: (int) $data['hotel_id'],
                    roomTypeId: (int) $data['room_type_id'],
                    checkInDate: $data['check_in_date'],
                    checkOutDate: $data['check_out_date'],
                    numberOfGuests: $data['number_of_guests'],
                    specialRequests: $data['special_requests'] ?? null,
                    correlationId: $correlationId,
                );

                $this->logger->info('Hotel booking created', [
                    'correlation_id' => $correlationId,
                    'booking_id' => $booking->id ?? null,
                    'hotel_id' => $data['hotel_id'],
                    'user_id' => $request->user()?->id,
                    'check_in' => $data['check_in_date'],
                    'check_out' => $data['check_out_date'],
                ]);

                return new \Illuminate\Http\JsonResponse([
                    'success' => true,
                    'data' => $booking,
                    'correlation_id' => $correlationId,
                ], 201);
            } catch (\Throwable $e) {
                return new \Illuminate\Http\JsonResponse([
                    'success' => false,
                    'error' => $e->getMessage(),
                ], 500);
            }
        }

        public function update(\Illuminate\Http\Request $request, string $id): JsonResponse
        {
            $correlationId = Str::uuid()->toString();
            $this->fraud->check(userId: $request->user()?->id ?? 0, operationType: 'operation', amount: 0, correlationId: $correlationId ?? '');

            try {
                $booking = Booking::findOrFail($id);
                $this->authorize('update', $booking);

                $data = $request->validate([
                    'booking_status' => 'nullable|in:confirmed,checked_in,checked_out,cancelled',
                    'special_requests' => 'nullable|string',
                ]);

                $before = $booking->booking_status;
                $booking->update($data);

                $this->logger->info('Hotel booking updated', [
                    'correlation_id' => $correlationId,
                    'booking_id' => $booking->id,
                    'user_id' => $request->user()?->id,
                    'before' => $before,
                    'after' => $data,
                ]);

                return new \Illuminate\Http\JsonResponse([
                    'success' => true,
                    'data' => $booking,
                ]);
            } catch (\Throwable $e) {
                return new \Illuminate\Http\JsonResponse([
                    'success' => false,
                    'error' => $e->getMessage(),
                ], 500);
            }
        }

        public function cancel(\Illuminate\Http\Request $request, string $id): JsonResponse
        {
            try {
                $booking = Booking::findOrFail($id);
                $this->authorize('cancel', $booking);

                $correlationId = Str::uuid()->toString();

                $this->fraud->check(userId: $request->user()?->id ?? 0, operationType: 'booking_cancel', amount: 0, correlationId: $correlationId ?? '');

                $this->bookingService->cancelBooking(
                    booking: $booking,
                    reason: $request->input('reason', 'Guest cancelled'),
                    correlationId: $correlationId,
                );

                $this->logger->info('Hotel booking cancelled', [
                    'correlation_id' => $correlationId,
                    'booking_id' => $booking->id,
                    'user_id' => $request->user()?->id,
                    'reason' => $request->input('reason', 'Guest cancelled'),
                ]);

                return new \Illuminate\Http\JsonResponse([
                    'success' => true,
                    'correlation_id' => $correlationId,
                ]);
            } catch (\Throwable $e) {
                return new \Illuminate\Http\JsonResponse([
                    'success' => false,
                    'error' => $e->getMessage(),
                ], 500);
            }
        }

        public function hotelBookings(string $hotelId): JsonResponse
        {
            try {
                $this->authorize('viewAny', Booking::class);

                $bookings = Booking::where('hotel_id', $hotelId)
                    ->paginate(20);

                return new \Illuminate\Http\JsonResponse([
                    'success' => true,
                    'data' => $bookings,
                ]);
            } catch (\Throwable $e) {
                return new \Illuminate\Http\JsonResponse([
                    'success' => false,
                    'error' => $e->getMessage(),
                ], 500);
            }
        }
}
