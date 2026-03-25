<?php declare(strict_types=1);

namespace App\Domains\Entertainment\Http\Controllers;

use App\Domains\Entertainment\Models\Booking;
use App\Domains\Entertainment\Services\BookingService;
use App\Domains\Entertainment\Services\TicketingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

final class BookingController
{
    public function __construct(
        private readonly BookingService $bookingService,
        private readonly TicketingService $ticketingService,
        private readonly FraudControlService $fraudControlService,) {}

    public function store(): JsonResponse
    {
        $fraudResult = $this->fraudControlService->check(
            auth()->id() ?? 0,
            'operation',
            0,
            request()->ip(),
            request()->header('X-Device-Fingerprint'),
            $correlationId,
        );

        if ($fraudResult['decision'] === 'block') {
            $this->log->channel('fraud_alert')->warning('Operation blocked by fraud control', [
                'correlation_id' => $correlationId,
                'user_id'        => auth()->id(),
                'score'          => $fraudResult['score'],
            ]);
            return response()->json([
                'success'        => false,
                'error'          => 'Операция заблокирована.',
                'correlation_id' => $correlationId,
            ], 403);
        }

        try {
            $correlationId = Str::uuid()->toString();

            $this->db->transaction(function () use ($correlationId) {
                $booking = $this->bookingService->createBooking(
                    request('venue_id'),
                    request('event_schedule_id'),
                    auth()->id(),
                    request('number_of_seats'),
                    $correlationId,
                );

                $this->ticketingService->generateTickets($booking, $correlationId);
            });

            return response()->json(['success' => true, 'data' => null, 'correlation_id' => $correlationId], 201);
        } catch (\Throwable $e) {
            $this->log->channel('audit')->error('Failed to create booking', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => $e->getMessage(), 'correlation_id' => Str::uuid()], 400);
        }
    }

    public function myBookings(): JsonResponse
    {
        try {
            $bookings = Booking::where('customer_id', auth()->id())
                ->with('venue', 'eventSchedule')
                ->paginate(20);

            return response()->json(['success' => true, 'data' => $bookings, 'correlation_id' => Str::uuid()]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage(), 'correlation_id' => Str::uuid()], 500);
        }
    }

    public function show(int $id): JsonResponse
    {
        try {
            $booking = Booking::findOrFail($id);
            $this->authorize('view', $booking);

            return response()->json(['success' => true, 'data' => $booking->load('venue', 'eventSchedule', 'tickets'), 'correlation_id' => Str::uuid()]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage(), 'correlation_id' => Str::uuid()], 500);
        }
    }

    public function update(int $id): JsonResponse
    {
        $fraudResult = $this->fraudControlService->check(
            auth()->id() ?? 0,
            'operation',
            0,
            request()->ip(),
            request()->header('X-Device-Fingerprint'),
            $correlationId,
        );

        if ($fraudResult['decision'] === 'block') {
            $this->log->channel('fraud_alert')->warning('Operation blocked by fraud control', [
                'correlation_id' => $correlationId,
                'user_id'        => auth()->id(),
                'score'          => $fraudResult['score'],
            ]);
            return response()->json([
                'success'        => false,
                'error'          => 'Операция заблокирована.',
                'correlation_id' => $correlationId,
            ], 403);
        }

        try {
            $booking = Booking::findOrFail($id);
            $correlationId = Str::uuid()->toString();

            $this->db->transaction(function () use ($booking, $correlationId) {
                $booking->update(['correlation_id' => $correlationId]);
                $this->log->channel('audit')->info('Booking updated', ['booking_id' => $id, 'correlation_id' => $correlationId]);
            });

            return response()->json(['success' => true, 'data' => $booking, 'correlation_id' => $correlationId]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage(), 'correlation_id' => Str::uuid()], 500);
        }
    }

    public function cancel(int $id): JsonResponse
    {
        try {
            $booking = Booking::findOrFail($id);
            $correlationId = Str::uuid()->toString();

            $this->authorize('cancel', $booking);

            $this->db->transaction(function () use ($booking, $correlationId) {
                $this->bookingService->cancelBooking($booking, request('reason'), $correlationId);
                $this->ticketingService->refundTickets($booking, $correlationId);
            });

            return response()->json(['success' => true, 'data' => null, 'correlation_id' => $correlationId]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage(), 'correlation_id' => Str::uuid()], 500);
        }
    }

    public function confirm(int $id): JsonResponse
    {
        try {
            $booking = Booking::findOrFail($id);
            $correlationId = Str::uuid()->toString();

            $this->db->transaction(function () use ($booking, $correlationId) {
                $booking->update(['status' => 'confirmed', 'correlation_id' => $correlationId]);
                $this->log->channel('audit')->info('Booking confirmed', ['booking_id' => $id, 'correlation_id' => $correlationId]);
            });

            return response()->json(['success' => true, 'data' => $booking, 'correlation_id' => $correlationId]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage(), 'correlation_id' => Str::uuid()], 500);
        }
    }

    public function expire(int $id): JsonResponse
    {
        try {
            $booking = Booking::findOrFail($id);
            $correlationId = Str::uuid()->toString();

            $this->db->transaction(function () use ($booking, $correlationId) {
                $booking->update(['status' => 'completed', 'correlation_id' => $correlationId]);
                $this->log->channel('audit')->info('Booking completed', ['booking_id' => $id, 'correlation_id' => $correlationId]);
            });

            return response()->json(['success' => true, 'data' => null, 'correlation_id' => $correlationId]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage(), 'correlation_id' => Str::uuid()], 500);
        }
    }
}
