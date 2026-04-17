<?php

declare(strict_types=1);

namespace App\Domains\Sports\Http\Controllers;

use App\Domains\Sports\DTOs\RealTimeBookingDto;
use App\Domains\Sports\Services\SportsRealTimeBookingService;
use App\Domains\Sports\Models\Booking;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Str;

final class RealTimeBookingController extends Controller
{
    public function __construct(
        private SportsRealTimeBookingService $service,
    ) {}

    public function holdSlot(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'venue_id' => 'required|integer|exists:sports_gyms,id',
            'trainer_id' => 'nullable|integer|exists:sports_trainers,id',
            'slot_start' => 'required|date|after:now',
            'slot_end' => 'required|date|after:slot_start',
            'booking_type' => 'required|string|in:gym_access,personal_training,group_class',
            'biometric_data' => 'sometimes|array',
            'extended_hold' => 'sometimes|boolean',
        ]);

        $dto = new RealTimeBookingDto(
            userId: auth()->id(),
            tenantId: tenant()->id,
            businessGroupId: $request->get('business_group_id'),
            venueId: $validated['venue_id'],
            trainerId: $validated['trainer_id'] ?? null,
            slotStart: $validated['slot_start'],
            slotEnd: $validated['slot_end'],
            bookingType: $validated['booking_type'],
            biometricData: $validated['biometric_data'] ?? [],
            extendedHold: $validated['extended_hold'] ?? false,
            correlationId: $request->header('X-Correlation-ID', Str::uuid()->toString()),
        );

        $result = $this->service->holdSlot($dto);

        return response()->json($result);
    }

    public function confirmBooking(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'venue_id' => 'required|integer|exists:sports_gyms,id',
            'trainer_id' => 'nullable|integer|exists:sports_trainers,id',
            'slot_start' => 'required|date',
            'slot_end' => 'required|date|after:slot_start',
            'booking_type' => 'required|string|in:gym_access,personal_training,group_class',
            'biometric_data' => 'sometimes|array',
            'extended_hold' => 'sometimes|boolean',
            'payment_data' => 'required|array',
            'payment_data.amount' => 'required|numeric|min:0',
            'payment_data.transaction_id' => 'required|string',
        ]);

        $dto = new RealTimeBookingDto(
            userId: auth()->id(),
            tenantId: tenant()->id,
            businessGroupId: $request->get('business_group_id'),
            venueId: $validated['venue_id'],
            trainerId: $validated['trainer_id'] ?? null,
            slotStart: $validated['slot_start'],
            slotEnd: $validated['slot_end'],
            bookingType: $validated['booking_type'],
            biometricData: $validated['biometric_data'] ?? [],
            extendedHold: $validated['extended_hold'] ?? false,
            correlationId: $request->header('X-Correlation-ID', Str::uuid()->toString()),
        );

        $booking = $this->service->confirmBooking($dto, $validated['payment_data']);

        return response()->json([
            'booking' => $booking->toArray(),
        ]);
    }

    public function releaseSlot(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'venue_id' => 'required|integer|exists:sports_gyms,id',
            'trainer_id' => 'nullable|integer|exists:sports_trainers,id',
            'slot_start' => 'required|date',
        ]);

        $this->service->releaseSlot(
            venueId: $validated['venue_id'],
            trainerId: $validated['trainer_id'] ?? null,
            slotStart: $validated['slot_start'],
            userId: auth()->id(),
            correlationId: $request->header('X-Correlation-ID', Str::uuid()->toString()),
        );

        return response()->json([
            'success' => true,
            'message' => 'Slot released successfully',
        ]);
    }

    public function getAvailableSlots(Request $request, int $venueId): JsonResponse
    {
        $validated = $request->validate([
            'trainer_id' => 'nullable|integer|exists:sports_trainers,id',
            'date' => 'required|date|after:today',
        ]);

        $slots = $this->service->getAvailableSlots(
            venueId: $venueId,
            trainerId: $validated['trainer_id'] ?? null,
            date: $validated['date'],
        );

        return response()->json([
            'slots' => $slots,
        ]);
    }

    public function verifyBiometricCheckIn(Request $request, int $bookingId): JsonResponse
    {
        $validated = $request->validate([
            'biometric_data' => 'required|array',
        ]);

        $booking = Booking::findOrFail($bookingId);

        $verified = $this->service->verifyBiometricOnCheckIn(
            bookingId: $bookingId,
            biometricData: $validated['biometric_data'],
            correlationId: $request->header('X-Correlation-ID', Str::uuid()->toString()),
        );

        return response()->json([
            'verified' => $verified,
            'message' => $verified ? 'Check-in successful' : 'Biometric verification failed',
        ]);
    }

    public function extendHold(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'venue_id' => 'required|integer|exists:sports_gyms,id',
            'trainer_id' => 'nullable|integer|exists:sports_trainers,id',
            'slot_start' => 'required|date',
        ]);

        $result = $this->service->extendHold(
            venueId: $validated['venue_id'],
            trainerId: $validated['trainer_id'] ?? null,
            slotStart: $validated['slot_start'],
            userId: auth()->id(),
            correlationId: $request->header('X-Correlation-ID', Str::uuid()->toString()),
        );

        return response()->json($result);
    }
}
