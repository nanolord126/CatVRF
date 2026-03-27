<?php
declare(strict_types=1);
namespace App\Http\Controllers\Api\Entertainment;
use App\Domains\EventPlanning\Entertainment\Models\Event;
use App\Domains\EventPlanning\Entertainment\Models\Venue;
use App\Domains\EventPlanning\Entertainment\Services\BookingService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Entertainment\BookSeatRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
/**
 * КАНОН 2026 — PUBLIC ENTERTAINMENT API CONTROLLER
 * 1. final class
 * 2. try/catch + correlation_id
 * 3. DB Transactions via Services
 */
final class PublicEventController extends Controller
{
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
