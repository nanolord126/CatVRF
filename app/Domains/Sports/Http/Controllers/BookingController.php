<?php declare(strict_types=1);

namespace App\Domains\Sports\Http\Controllers;

use App\Domains\Sports\Models\Booking;
use App\Domains\Sports\Services\BookingService;
use App\Domains\Sports\Jobs\BookingConfirmationJob;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

final class BookingController
{
    public function __construct(private BookingService $bookingService) {}

    public function create(int $classId): JsonResponse
    {
        if (class_exists('\App\Services\FraudControlService')) {
            \App\Services\FraudControlService::check();
        }

        try {
            $correlationId = Str::uuid();

            $booking = DB::transaction(function () use ($classId, $correlationId) {
                return $this->bookingService->createBooking(
                    $classId,
                    auth()->id(),
                    null,
                    'class',
                    request()->input('price', 0),
                    false,
                    $correlationId
                );
            });

            BookingConfirmationJob::dispatch($booking->id, $correlationId);

            return response()->json(['success' => true, 'data' => $booking, 'correlation_id' => $correlationId], 201);
        } catch (\Throwable $e) {
            \Log::channel('audit')->error('Booking creation failed', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'Booking failed'], 500);
        }
    }

    public function myBookings(): JsonResponse
    {
        try {
            $bookings = Booking::where('member_id', auth()->id())
                ->with(['class', 'trainer'])
                ->paginate(20);

            return response()->json(['success' => true, 'data' => $bookings, 'correlation_id' => Str::uuid()]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => 'Failed to list bookings'], 500);
        }
    }

    public function show(int $id): JsonResponse
    {
        try {
            $booking = Booking::findOrFail($id);
            $this->authorize('view', $booking);

            return response()->json(['success' => true, 'data' => $booking, 'correlation_id' => Str::uuid()]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => 'Booking not found'], 404);
        }
    }

    public function cancel(int $id): JsonResponse
    {
        try {
            $booking = Booking::findOrFail($id);
            $this->authorize('cancel', $booking);

            $correlationId = Str::uuid();
            DB::transaction(fn() => $this->bookingService->cancelBooking($booking, 'User cancelled', $correlationId));

            return response()->json(['success' => true, 'message' => 'Booking cancelled', 'correlation_id' => $correlationId]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => 'Failed to cancel booking'], 500);
        }
    }

    public function markAttended(int $id): JsonResponse
    {
        try {
            $booking = Booking::findOrFail($id);
            $correlationId = Str::uuid();
            $this->bookingService->markAsAttended($booking, $correlationId);

            return response()->json(['success' => true, 'message' => 'Booking marked as attended', 'correlation_id' => $correlationId]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => 'Failed to mark attendance'], 500);
        }
    }
}
