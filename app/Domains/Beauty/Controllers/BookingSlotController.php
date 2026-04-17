<?php declare(strict_types=1);

namespace App\Domains\Beauty\Controllers;

use App\Domains\Beauty\DTOs\HoldBookingSlotDto;
use App\Domains\Beauty\Requests\ConfirmBookingSlotRequest;
use App\Domains\Beauty\Requests\HoldBookingSlotRequest;
use App\Domains\Beauty\Requests\ReleaseBookingSlotRequest;
use App\Domains\Beauty\Resources\BookingSlotResource;
use App\Domains\Beauty\Services\BookingSlotHoldService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

final class BookingSlotController
{
    public function __construct(
        private BookingSlotHoldService $slotHoldService,
    ) {
    }

    public function hold(HoldBookingSlotRequest $request): JsonResponse
    {
        $correlationId = $request->getCorrelationId();

        Log::channel('audit')->info('beauty.controller.hold.start', [
            'correlation_id' => $correlationId,
            'booking_slot_id' => $request->input('booking_slot_id'),
            'customer_id' => $request->input('customer_id'),
        ]);

        try {
            $dto = HoldBookingSlotDto::fromArray([
                'booking_slot_id' => $request->input('booking_slot_id'),
                'customer_id' => $request->input('customer_id'),
                'tenant_id' => $request->input('tenant_id'),
                'business_group_id' => $request->input('business_group_id'),
                'is_b2b' => $request->input('is_b2b', false),
                'correlation_id' => $correlationId,
                'idempotency_key' => $request->getIdempotencyKey(),
            ]);

            $slot = $this->slotHoldService->holdSlot($dto);

            Log::channel('audit')->info('beauty.controller.hold.success', [
                'correlation_id' => $correlationId,
                'booking_slot_id' => $slot->id,
            ]);

            return new JsonResponse([
                'success' => true,
                'data' => new BookingSlotResource($slot),
                'correlation_id' => $correlationId,
            ], 201);
        } catch (\Throwable $e) {
            Log::channel('audit')->critical('beauty.controller.hold.error', [
                'correlation_id' => $correlationId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return new JsonResponse([
                'success' => false,
                'message' => $e->getMessage(),
                'correlation_id' => $correlationId,
            ], 422);
        }
    }

    public function release(ReleaseBookingSlotRequest $request): JsonResponse
    {
        $correlationId = $request->getCorrelationId();

        Log::channel('audit')->info('beauty.controller.release.start', [
            'correlation_id' => $correlationId,
            'booking_slot_id' => $request->input('booking_slot_id'),
        ]);

        try {
            $slot = $this->slotHoldService->releaseSlot(
                bookingSlotId: $request->input('booking_slot_id'),
                tenantId: $request->input('tenant_id'),
                reason: $request->input('reason', 'manual'),
                correlationId: $correlationId,
            );

            Log::channel('audit')->info('beauty.controller.release.success', [
                'correlation_id' => $correlationId,
                'booking_slot_id' => $slot->id,
            ]);

            return new JsonResponse([
                'success' => true,
                'data' => new BookingSlotResource($slot),
                'correlation_id' => $correlationId,
            ]);
        } catch (\Throwable $e) {
            Log::channel('audit')->critical('beauty.controller.release.error', [
                'correlation_id' => $correlationId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return new JsonResponse([
                'success' => false,
                'message' => $e->getMessage(),
                'correlation_id' => $correlationId,
            ], 422);
        }
    }

    public function confirm(ConfirmBookingSlotRequest $request): JsonResponse
    {
        $correlationId = $request->getCorrelationId();

        Log::channel('audit')->info('beauty.controller.confirm.start', [
            'correlation_id' => $correlationId,
            'booking_slot_id' => $request->input('booking_slot_id'),
            'order_id' => $request->input('order_id'),
        ]);

        try {
            $slot = $this->slotHoldService->confirmSlotAsBooked(
                bookingSlotId: $request->input('booking_slot_id'),
                tenantId: $request->input('tenant_id'),
                orderId: $request->input('order_id'),
                correlationId: $correlationId,
            );

            Log::channel('audit')->info('beauty.controller.confirm.success', [
                'correlation_id' => $correlationId,
                'booking_slot_id' => $slot->id,
            ]);

            return new JsonResponse([
                'success' => true,
                'data' => new BookingSlotResource($slot),
                'correlation_id' => $correlationId,
            ]);
        } catch (\Throwable $e) {
            Log::channel('audit')->critical('beauty.controller.confirm.error', [
                'correlation_id' => $correlationId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return new JsonResponse([
                'success' => false,
                'message' => $e->getMessage(),
                'correlation_id' => $correlationId,
            ], 422);
        }
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID') ?? \Illuminate\Support\Str::uuid()->toString();

        try {
            $slot = \App\Domains\Beauty\Models\BookingSlot::query()
                ->where('id', $id)
                ->where('tenant_id', tenant()->id)
                ->with(['salon', 'master', 'service', 'customer', 'order'])
                ->firstOrFail();

            return new JsonResponse([
                'success' => true,
                'data' => new BookingSlotResource($slot),
                'correlation_id' => $correlationId,
            ]);
        } catch (\Throwable $e) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Booking slot not found',
                'correlation_id' => $correlationId,
            ], 404);
        }
    }
}
