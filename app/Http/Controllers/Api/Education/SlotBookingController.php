<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Education;

use App\Http\Controllers\Controller;
use App\Domains\Education\Requests\BookSlotRequest;
use App\Domains\Education\DTOs\BookSlotDto;
use App\Domains\Education\Services\EducationSlotBookingService;
use App\Domains\Education\Events\SlotBookedEvent;
use Illuminate\Http\JsonResponse;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

final readonly class SlotBookingController extends Controller
{
    public function __construct(
        private readonly Dispatcher $events,
        private readonly EducationSlotBookingService $bookingService,
    ) {}

    public function hold(int $slotId, Request $request): JsonResponse
    {
        $request->validate([
            'user_id' => ['required', 'integer', 'exists:users,id'],
        ]);

        $correlationId = $request->header('X-Correlation-ID') ?? (string) Str::uuid();
        $userId = (int) $request->input('user_id');

        $hold = $this->bookingService->holdSlot($slotId, $userId, $correlationId);

        return new JsonResponse($hold->toArray())
            ->header('X-Correlation-ID', $correlationId);
    }

    public function release(int $slotId, Request $request): JsonResponse
    {
        $request->validate([
            'user_id' => ['required', 'integer', 'exists:users,id'],
        ]);

        $correlationId = $request->header('X-Correlation-ID') ?? (string) Str::uuid();
        $userId = (int) $request->input('user_id');

        $this->bookingService->releaseSlotHold($slotId, $userId, $correlationId);

        return new JsonResponse([
            'message' => 'Slot hold released',
            'slot_id' => $slotId,
        ])
            ->header('X-Correlation-ID', $correlationId);
    }

    public function book(BookSlotRequest $request): JsonResponse
    {
        $dto = BookSlotDto::from($request);

        $result = $this->bookingService->bookSlot($dto);

        $this->events->dispatch(new SlotBookedEvent(
            bookingId: $result['booking_id'],
            bookingReference: $result['booking_reference'],
            slotId: $dto->slotId,
            userId: $dto->userId,
            tenantId: $dto->tenantId,
            businessGroupId: $dto->businessGroupId,
            correlationId: $dto->correlationId,
        ));

        return new JsonResponse($result)
            ->setStatusCode(201)
            ->header('X-Correlation-ID', $dto->correlationId);
    }

    public function cancel(int $bookingId, Request $request): JsonResponse
    {
        $request->validate([
            'user_id' => ['required', 'integer', 'exists:users,id'],
        ]);

        $correlationId = $request->header('X-Correlation-ID') ?? (string) Str::uuid();
        $userId = (int) $request->input('user_id');

        $this->bookingService->cancelBooking($bookingId, $userId, $correlationId);

        return new JsonResponse([
            'message' => 'Booking cancelled',
            'booking_id' => $bookingId,
        ])
            ->header('X-Correlation-ID', $correlationId);
    }
}
