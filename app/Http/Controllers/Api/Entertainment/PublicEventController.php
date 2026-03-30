<?php declare(strict_types=1);

namespace App\Http\Controllers\Api\Entertainment;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class PublicEventController extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    public function __construct(
            private readonly BookingService $bookingService
        ) {
        }
        /**
         * Список активных заведений
         */
        public function listVenues(Request $request): JsonResponse
        {
            $correlationId = $request->header('X-Correlation-ID', (string) Str::uuid());
            try {
                $venues = Venue::where('is_active', true)
                    ->where('tenant_id', tenant()->id)
                    ->orderBy('rating', 'desc')
                    ->get();
                return response()->json([
                    'success' => true,
                    'data' => $venues,
                    'correlation_id' => $correlationId
                ]);
            } catch (\Throwable $e) {
                Log::channel('audit')->error('Failed to list venues', [
                    'error' => $e->getMessage(),
                    'correlation_id' => $correlationId
                ]);
                return response()->json(['error' => 'Internal Server Error'], 500);
            }
        }
        /**
         * Поиск событий
         */
        public function searchEvents(Request $request): JsonResponse
        {
            $correlationId = $request->header('X-Correlation-ID', (string) Str::uuid());
            try {
                $query = Event::where('tenant_id', tenant()->id)
                    ->where('status', 'on_sale')
                    ->with('venue');
                if ($request->has('venue_id')) {
                    $query->where('venue_id', $request->get('venue_id'));
                }
                $events = $query->orderBy('start_at', 'asc')->paginate(15);
                return response()->json([
                    'success' => true,
                    'data' => $events,
                    'correlation_id' => $correlationId
                ]);
            } catch (\Throwable $e) {
                Log::channel('audit')->error('Event search failed', [
                    'error' => $e->getMessage(),
                    'correlation_id' => $correlationId
                ]);
                return response()->json(['error' => 'Search failed'], 500);
            }
        }
        /**
         * Инициация бронирования
         */
        public function book(BookSeatRequest $request): JsonResponse
        {
            $correlationId = $request->get('correlation_id', (string) Str::uuid());
            try {
                $booking = $this->bookingService->createBooking(
                    userId: auth()->id() ?? 0, // Fallback for guest if allowed
                    eventId: $request->integer('event_id'),
                    seats: $request->get('seats'),
                    correlationId: $correlationId
                );
                return response()->json([
                    'success' => true,
                    'booking_uuid' => $booking->uuid,
                    'total_amount' => $booking->total_amount_kopecks,
                    'expires_at' => now()->addMinutes(20)->toIso8601String(),
                    'correlation_id' => $correlationId
                ], 201);
            } catch (\Exception $e) {
                return response()->json([
                    'success' => false,
                    'error' => $e->getMessage(),
                    'correlation_id' => $correlationId
                ], 400);
            }
        }
}
