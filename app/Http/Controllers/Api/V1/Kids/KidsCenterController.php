<?php declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Kids;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class KidsCenterController extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    public function __construct(
            private readonly KidsCenterService $centerService,
            private readonly FraudControlService $fraud,
        ) {}
        /**
         * Get children centers with location filtering.
         * GET /api/v1/kids/centers
         */
        public function index(Request $request): JsonResponse
        {
            $correlationId = (string) Str::uuid();
            $centers = KidsCenter::query()
                ->with(['store', 'events'])
                ->when($request->get('type'), fn($q) => $q->where('center_type', $request->get('type')))
                ->when($request->get('verified'), fn($q) => $q->where('is_safety_verified', true))
                ->latest()
                ->paginate($request->get('limit', 20));
            return response()->json([
                'success' => true,
                'data' => $centers,
                'correlation_id' => $correlationId,
            ]);
        }
        /**
         * Get specific center details.
         * GET /api/v1/kids/centers/{id}
         */
        public function show(string $id): JsonResponse
        {
            $correlationId = (string) Str::uuid();
            $center = KidsCenter::with(['store', 'events', 'reviews'])
                ->findOrFail($id);
            return response()->json([
                'success' => true,
                'data' => $center,
                'correlation_id' => $correlationId,
            ]);
        }
        /**
         * Book ticket/entry to a center event.
         * POST /api/v1/kids/centers/{id}/book-event
         */
        public function bookEvent(Request $request, string $id): JsonResponse
        {
            $correlationId = (string) Str::uuid();
            $request->validate([
                'event_id' => 'required|integer',
                'tickets_count' => 'required|integer|min:1|max:5',
                'child_age_months' => 'nullable|integer',
            ]);
            $this->fraud->check('kids_center_booking', $request->ip());
            try {
                $event = KidsEvent::findOrFail($request->get('event_id'));
                // Business rule: Event must belong to the center
                if ((int) $event->center_id !== (int) $id) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Requested event does not belong to this center.',
                        'correlation_id' => $correlationId,
                    ], 403);
                }
                $bookingResult = $this->centerService->bookEvent(
                    centerId: (int) $id,
                    eventId: (int) $event->id,
                    userId: (int) $request->user()->id,
                    correlationId: $correlationId
                );
                if (!$bookingResult) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Booking failed: Center at capacity or event expired.',
                        'correlation_id' => $correlationId,
                    ], 400);
                }
                return response()->json([
                    'success' => true,
                    'message' => 'Event successfully booked.',
                    'correlation_id' => $correlationId,
                ]);
            } catch (\Throwable $e) {
                Log::channel('audit')->error('Kids Center Booking API Failure', [
                    'error' => $e->getMessage(),
                    'correlation_id' => $correlationId,
                ]);
                return response()->json([
                    'success' => false,
                    'message' => 'Booking system failure.',
                    'correlation_id' => $correlationId,
                ], 500);
            }
        }
}
