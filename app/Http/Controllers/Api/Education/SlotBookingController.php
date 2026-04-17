<?php declare(strict_types=1);

namespace App\Http\Controllers\Api\Education;

use App\Http\Controllers\Controller;
use App\Domains\Education\Requests\BookSlotRequest;
use App\Domains\Education\DTOs\BookSlotDto;
use App\Domains\Education\Services\EducationSlotBookingService;
use App\Domains\Education\Events\SlotBookedEvent;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Event;

final readonly class SlotBookingController extends Controller
{
    public function __construct(
        private EducationSlotBookingService $bookingService,
    ) {}

    public function hold(int $slotId, \Illuminate\Http\Request $request): JsonResponse
    {
        $request->validate([
            'user_id' => ['required', 'integer', 'exists:users,id'],
        ]);

        $correlationId = $request->header('X-Correlation-ID') ?? (string) \Illuminate\Support\Str::uuid();
        $userId = (int) $request->input('user_id');

        $hold = $this->bookingService->holdSlot($slotId, $userId, $correlationId);

        return response()->json($hold->toArray())
            ->header('X-Correlation-ID', $correlationId);
    }

    public function release(int $slotId, \Illuminate\Http\Request $request): JsonResponse
    {
        $request->validate([
            'user_id' => ['required', 'integer', 'exists:users,id'],
        ]);

        $correlationId = $request->header('X-Correlation-ID') ?? (string) \Illuminate\Support\Str::uuid();
        $userId = (int) $request->input('user_id');

        $this->bookingService->releaseSlotHold($slotId, $userId, $correlationId);

        return response()->json([
            'message' => 'Slot hold released',
            'slot_id' => $slotId,
        ])
            ->header('X-Correlation-ID', $correlationId);
    }

    public function book(BookSlotRequest $request): JsonResponse
    {
        $dto = BookSlotDto::from($request);

        $result = $this->bookingService->bookSlot($dto);

        Event::dispatch(new SlotBookedEvent(
            bookingId: $result['booking_id'],
            bookingReference: $result['booking_reference'],
            slotId: $dto->slotId,
            userId: $dto->userId,
            tenantId: $dto->tenantId,
            businessGroupId: $dto->businessGroupId,
            correlationId: $dto->correlationId,
        ));

        return response()->json($result)
            ->setStatusCode(201)
            ->header('X-Correlation-ID', $dto->correlationId);
    }

    public function cancel(int $bookingId, \Illuminate\Http\Request $request): JsonResponse
    {
        $request->validate([
            'user_id' => ['required', 'integer', 'exists:users,id'],
        ]);

        $correlationId = $request->header('X-Correlation-ID') ?? (string) \Illuminate\Support\Str::uuid();
        $userId = (int) $request->input('user_id');

        $this->bookingService->cancelBooking($bookingId, $userId, $correlationId);

        return response()->json([
            'message' => 'Booking cancelled',
            'booking_id' => $bookingId,
        ])
            ->header('X-Correlation-ID', $correlationId);
    }
}
