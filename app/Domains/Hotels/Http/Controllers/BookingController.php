<?php declare(strict_types=1);

namespace App\Domains\Hotels\Http\Controllers;

use App\Domains\Hotels\Models\Booking;
use App\Domains\Hotels\Services\BookingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;

final class BookingController extends Controller
{
    public function __construct(
        private readonly BookingService $bookingService,
    ) {}

    public function index(): JsonResponse
    {
        try {
            $this->authorize('viewAny', Booking::class);

            $bookings = Booking::where('guest_id', auth()->id())
                ->paginate(10);

            return response()->json([
                'success' => true,
                'data' => $bookings,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
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

            return response()->json([
                'success' => true,
                'data' => $booking->load(['hotel', 'roomType']),
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function store(): JsonResponse
    {
        if (class_exists('\App\Services\FraudControlService')) {
            \App\Services\FraudControlService::check();
        }

        try {
            $this->authorize('create', Booking::class);

            $data = request()->validate([
                'hotel_id' => 'required|uuid',
                'room_type_id' => 'required|uuid',
                'check_in_date' => 'required|date',
                'check_out_date' => 'required|date|after:check_in_date',
                'number_of_guests' => 'required|integer|min:1',
                'special_requests' => 'nullable|string',
            ]);

            $correlationId = \Illuminate\Support\Str::uuid();

            $booking = $this->bookingService->createBooking(
                hotelId: (int) $data['hotel_id'],
                roomTypeId: (int) $data['room_type_id'],
                checkInDate: $data['check_in_date'],
                checkOutDate: $data['check_out_date'],
                numberOfGuests: $data['number_of_guests'],
                specialRequests: $data['special_requests'] ?? null,
                correlationId: $correlationId,
            );

            return response()->json([
                'success' => true,
                'data' => $booking,
                'correlation_id' => $correlationId,
            ], 201);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function update(string $id): JsonResponse
    {
        if (class_exists('\App\Services\FraudControlService')) {
            \App\Services\FraudControlService::check();
        }

        try {
            $booking = Booking::findOrFail($id);
            $this->authorize('update', $booking);

            $data = request()->validate([
                'booking_status' => 'nullable|in:confirmed,checked_in,checked_out,cancelled',
                'special_requests' => 'nullable|string',
            ]);

            $booking->update($data);

            return response()->json([
                'success' => true,
                'data' => $booking,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function cancel(string $id): JsonResponse
    {
        try {
            $booking = Booking::findOrFail($id);
            $this->authorize('cancel', $booking);

            $correlationId = \Illuminate\Support\Str::uuid();

            $this->bookingService->cancelBooking(
                booking: $booking,
                reason: request()->input('reason', 'Guest cancelled'),
                correlationId: $correlationId,
            );

            return response()->json([
                'success' => true,
                'correlation_id' => $correlationId,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
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

            return response()->json([
                'success' => true,
                'data' => $bookings,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
