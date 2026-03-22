<?php declare(strict_types=1);

namespace App\Domains\Hotels\Http\Controllers;

use App\Domains\Hotels\Models\Booking;
use App\Domains\Hotels\Services\BookingService;
use App\Services\FraudControlService;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

final class BookingController extends Controller
{
    public function __construct(
        private readonly BookingService $bookingService,
        private readonly FraudControlService $fraudControlService,
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
        $correlationId = Str::uuid()->toString();
        $this->fraudControlService->check(auth()->id() ?? 0, 'operation', 0, request()->ip(), null, $correlationId);

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

            $booking = $this->bookingService->createBooking(
                hotelId: (int) $data['hotel_id'],
                roomTypeId: (int) $data['room_type_id'],
                checkInDate: $data['check_in_date'],
                checkOutDate: $data['check_out_date'],
                numberOfGuests: $data['number_of_guests'],
                specialRequests: $data['special_requests'] ?? null,
                correlationId: $correlationId,
            );

            Log::channel('audit')->info('Hotel booking created', [
                'correlation_id' => $correlationId,
                'booking_id' => $booking->id ?? null,
                'hotel_id' => $data['hotel_id'],
                'user_id' => auth()->id(),
                'check_in' => $data['check_in_date'],
                'check_out' => $data['check_out_date'],
            ]);

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
        $correlationId = Str::uuid()->toString();
        $this->fraudControlService->check(auth()->id() ?? 0, 'operation', 0, request()->ip(), null, $correlationId);

        try {
            $booking = Booking::findOrFail($id);
            $this->authorize('update', $booking);

            $data = request()->validate([
                'booking_status' => 'nullable|in:confirmed,checked_in,checked_out,cancelled',
                'special_requests' => 'nullable|string',
            ]);

            $before = $booking->booking_status;
            $booking->update($data);

            Log::channel('audit')->info('Hotel booking updated', [
                'correlation_id' => $correlationId,
                'booking_id' => $booking->id,
                'user_id' => auth()->id(),
                'before' => $before,
                'after' => $data,
            ]);

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

            $correlationId = Str::uuid()->toString();

            $this->fraudControlService->check(auth()->id() ?? 0, 'booking_cancel', 0, request()->ip(), null, $correlationId);

            $this->bookingService->cancelBooking(
                booking: $booking,
                reason: request()->input('reason', 'Guest cancelled'),
                correlationId: $correlationId,
            );

            Log::channel('audit')->info('Hotel booking cancelled', [
                'correlation_id' => $correlationId,
                'booking_id' => $booking->id,
                'user_id' => auth()->id(),
                'reason' => request()->input('reason', 'Guest cancelled'),
            ]);

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
