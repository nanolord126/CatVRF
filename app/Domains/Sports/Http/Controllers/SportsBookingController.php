<?php

declare(strict_types=1);

namespace App\Domains\Sports\Http\Controllers;

use App\Domains\Sports\DTOs\RealTimeBookingDto;
use App\Domains\Sports\Models\Booking;
use App\Domains\Sports\Services\SportsRealTimeBookingService;
use App\Domains\Sports\Policies\SportsBookingPolicy;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final readonly class SportsBookingController
{
    public function __construct(
        private SportsRealTimeBookingService $bookingService,
    ) {}

    public function holdSlot(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'venue_id' => 'required|integer|exists:sports_studios,id',
            'trainer_id' => 'nullable|integer|exists:sports_trainers,id',
            'slot_start' => 'required|date|after:now',
            'slot_end' => 'required|date|after:slot_start',
            'booking_type' => 'required|string|in:personal_training,group_class,general',
            'biometric_data' => 'nullable|array',
            'extended_hold' => 'boolean',
            'idempotency_key' => 'nullable|string|max:255',
        ]);

        $correlationId = $request->header('X-Correlation-ID') ?? Str::uuid()->toString();

        $dto = RealTimeBookingDto::from([
            'user_id' => $request->user()->id,
            'tenant_id' => tenant()->id,
            'business_group_id' => $request->user()->business_group_id,
            'venue_id' => (int) $validated['venue_id'],
            'trainer_id' => isset($validated['trainer_id']) ? (int) $validated['trainer_id'] : null,
            'slot_start' => $validated['slot_start'],
            'slot_end' => $validated['slot_end'],
            'booking_type' => $validated['booking_type'],
            'biometric_data' => $validated['biometric_data'] ?? [],
            'extended_hold' => $validated['extended_hold'] ?? false,
            'correlation_id' => $correlationId,
            'idempotency_key' => $validated['idempotency_key'] ?? null,
        ]);

        $result = $this->bookingService->holdSlot($dto);

        return response()->json([
            'success' => $result['success'],
            'message' => $result['message'],
            'data' => [
                'hold_until' => $result['hold_until'],
                'hold_id' => $result['hold_id'] ?? null,
                'biometric_verified' => $result['biometric_verified'] ?? false,
            ],
            'correlation_id' => $correlationId,
        ], $result['success'] ? 200 : 400);
    }

    public function confirmBooking(Request $request, string $holdId): JsonResponse
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:0',
            'payment_transaction_id' => 'nullable|string',
            'payment_method' => 'required|string|in:card,wallet,split',
        ]);

        $correlationId = $request->header('X-Correlation-ID') ?? Str::uuid()->toString();

        $holdData = $this->getHoldData($holdId);
        if (!$holdData) {
            throw ValidationException::withMessages([
                'hold_id' => ['Slot hold has expired or does not exist.'],
            ]);
        }

        $dto = RealTimeBookingDto::from([
            'user_id' => $request->user()->id,
            'tenant_id' => tenant()->id,
            'business_group_id' => $request->user()->business_group_id,
            'venue_id' => $holdData['venue_id'],
            'trainer_id' => $holdData['trainer_id'],
            'slot_start' => $holdData['slot_start'],
            'slot_end' => $holdData['slot_end'],
            'booking_type' => $holdData['booking_type'],
            'biometric_data' => $holdData['biometric_data'] ?? [],
            'extended_hold' => $holdData['extended'] ?? false,
            'correlation_id' => $correlationId,
        ]);

        try {
            $booking = $this->bookingService->confirmBooking($dto, [
                'amount' => $validated['amount'],
                'transaction_id' => $validated['payment_transaction_id'] ?? null,
                'payment_method' => $validated['payment_method'],
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Booking confirmed successfully',
                'data' => [
                    'booking_id' => $booking->id,
                    'booking_uuid' => $booking->uuid,
                    'status' => $booking->status,
                    'slot_start' => $booking->slot_start->toIso8601String(),
                    'slot_end' => $booking->slot_end->toIso8601String(),
                    'amount' => $booking->amount,
                    'biometric_verified' => !empty($booking->biometric_hash),
                ],
                'correlation_id' => $correlationId,
            ], 201);
        } catch (\RuntimeException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'correlation_id' => $correlationId,
            ], 400);
        }
    }

    public function cancelBooking(Request $request, int $bookingId): JsonResponse
    {
        $booking = Booking::findOrFail($bookingId);
        $this->authorize('delete', $booking);

        $correlationId = $request->header('X-Correlation-ID') ?? Str::uuid()->toString();

        try {
            $booking->update([
                'status' => 'cancelled',
                'cancelled_at' => now(),
                'cancellation_reason' => $request->input('reason') ?? 'User cancelled',
            ]);

            $this->bookingService->releaseSlot(
                $booking->venue_id,
                $booking->trainer_id,
                $booking->slot_start->toDateTimeString(),
                $booking->user_id,
                $correlationId
            );

            return response()->json([
                'success' => true,
                'message' => 'Booking cancelled successfully',
                'correlation_id' => $correlationId,
            ]);
        } catch (\RuntimeException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'correlation_id' => $correlationId,
            ], 400);
        }
    }

    public function verifyBiometric(Request $request, int $bookingId): JsonResponse
    {
        $validated = $request->validate([
            'biometric_data' => 'required|array',
        ]);

        $booking = Booking::findOrFail($bookingId);
        $this->authorize('verifyBiometric', $booking);

        $correlationId = $request->header('X-Correlation-ID') ?? Str::uuid()->toString();

        $verified = $this->bookingService->verifyBiometricOnCheckIn(
            $bookingId,
            $request->user()->id,
            $validated['biometric_data'],
            $correlationId
        );

        return response()->json([
            'success' => $verified,
            'message' => $verified ? 'Biometric verification successful' : 'Biometric verification failed',
            'correlation_id' => $correlationId,
        ], $verified ? 200 : 401);
    }

    public function getAvailableSlots(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'venue_id' => 'required|integer|exists:sports_studios,id',
            'trainer_id' => 'nullable|integer|exists:sports_trainers,id',
            'date' => 'required|date|after_or_equal:today',
        ]);

        $correlationId = $request->header('X-Correlation-ID') ?? Str::uuid()->toString();

        $slots = $this->bookingService->getAvailableSlots(
            (int) $validated['venue_id'],
            isset($validated['trainer_id']) ? (int) $validated['trainer_id'] : null,
            $validated['date']
        );

        return response()->json([
            'success' => true,
            'data' => [
                'venue_id' => $validated['venue_id'],
                'trainer_id' => $validated['trainer_id'] ?? null,
                'date' => $validated['date'],
                'slots' => $slots,
            ],
            'correlation_id' => $correlationId,
        ]);
    }

    public function extendHold(Request $request, string $holdId): JsonResponse
    {
        $holdData = $this->getHoldData($holdId);
        if (!$holdData) {
            return response()->json([
                'success' => false,
                'message' => 'Slot hold not found or has expired.',
            ], 404);
        }

        if ($holdData['user_id'] !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized to extend this hold.',
            ], 403);
        }

        $correlationId = $request->header('X-Correlation-ID') ?? Str::uuid()->toString();

        try {
            $result = $this->bookingService->extendHold(
                $holdData['venue_id'],
                $holdData['trainer_id'],
                $holdData['slot_start'],
                $request->user()->id,
                $correlationId
            );

            return response()->json([
                'success' => true,
                'message' => 'Hold extended successfully',
                'data' => [
                    'hold_until' => $result['hold_until'],
                ],
                'correlation_id' => $correlationId,
            ]);
        } catch (\RuntimeException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'correlation_id' => $correlationId,
            ], 400);
        }
    }

    public function index(Request $request): JsonResponse
    {
        $query = Booking::forUser($request->user()->id)
            ->with(['venue', 'trainer'])
            ->orderBy('slot_start', 'desc');

        if ($request->has('status')) {
            $query->withStatus($request->input('status'));
        }

        if ($request->has('venue_id')) {
            $query->forVenue((int) $request->input('venue_id'));
        }

        if ($request->has('trainer_id')) {
            $query->forTrainer((int) $request->input('trainer_id'));
        }

        $bookings = $query->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $bookings->items(),
            'pagination' => [
                'current_page' => $bookings->currentPage(),
                'per_page' => $bookings->perPage(),
                'total' => $bookings->total(),
                'last_page' => $bookings->lastPage(),
            ],
        ]);
    }

    public function show(Request $request, int $bookingId): JsonResponse
    {
        $booking = Booking::with(['venue', 'trainer', 'user'])
            ->findOrFail($bookingId);

        $this->authorize('view', $booking);

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $booking->id,
                'uuid' => $booking->uuid,
                'status' => $booking->status,
                'booking_type' => $booking->booking_type,
                'slot_start' => $booking->slot_start->toIso8601String(),
                'slot_end' => $booking->slot_end->toIso8601String(),
                'amount' => $booking->amount,
                'biometric_verified' => !empty($booking->biometric_hash),
                'venue' => $booking->venue?->only(['id', 'name', 'address']),
                'trainer' => $booking->trainer?->only(['id', 'name', 'specialization']),
                'can_be_cancelled' => $booking->canBeCancelled(),
                'check_in_time' => $booking->check_in_time?->toIso8601String(),
            ],
        ]);
    }

    private function getHoldData(string $holdId): ?array
    {
        $redis = app('redis')->connection();
        $data = $redis->get($holdId);
        
        return $data ? json_decode($data, true) : null;
    }
}
