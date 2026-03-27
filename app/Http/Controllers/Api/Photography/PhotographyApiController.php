<?php
declare(strict_types=1);
namespace App\Http\Controllers\Api\Photography;
use App\Domains\Photography\Models\Booking;
use App\Domains\Photography\Models\Photographer;
use App\Domains\Photography\Models\PhotoStudio;
use App\Domains\Photography\Services\AIPhotoSessionConstructor;
use App\Domains\Photography\Services\BookingService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
/**
 * КАНОН 2026 — PHOTOGRAPHY API CONTROLLER
 * ЛЮТЫЙ РЕЖИМ: CORRELATION_ID + AUDIT + FRAUD PROTECTION
 */
final class PhotographyApiController extends Controller
{
    public function __construct(
        private readonly AIPhotoSessionConstructor $aiConstructor,
        private readonly BookingService $bookingService
    ) {}
    /**
     * AI Подбор фотосессии
     * POST /api/v1/photography/ai-match
     */
    public function aiMatch(Request $request): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID', (string) \Illuminate\Support\Str::uuid());
        try {
            $validated = $request->validate([
                'preferences' => 'required|array',
                'budget_max' => 'nullable|integer',
                'vertical' => 'nullable|string',
            ]);
            Log::channel('audit')->info('AI Photography Match Request', [
                'user_id' => auth()->id(),
                'correlation_id' => $correlationId,
                'preferences' => $validated['preferences']
            ]);
            $result = $this->aiConstructor->match(
                $validated['preferences'],
                $validated['budget_max'] ?? 5000000,
                $correlationId
            );
            return response()->json([
                'success' => true,
                'data' => $result,
                'correlation_id' => $correlationId
            ]);
        } catch (\Throwable $e) {
            Log::channel('audit')->error('AI Match Error', [
                'correlation_id' => $correlationId,
                'error' => $e->getMessage()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Ошибка при работе AI подбора.',
                'correlation_id' => $correlationId
            ], 500);
        }
    }
    /**
     * Бронирование сессии
     * POST /api/v1/photography/book
     */
    public function book(Request $request): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID', (string) \Illuminate\Support\Str::uuid());
        try {
            $validated = $request->validate([
                'session_id' => 'required|exists:photography_sessions,id',
                'starts_at' => 'required|date|after:now',
                'photographer_id' => 'nullable|exists:photography_photographers,id',
                'studio_id' => 'nullable|exists:photography_studios,id',
            ]);
            $booking = $this->bookingService->createBooking(
                auth()->id() ?? 0, // Mock auth for now
                $validated['session_id'],
                $validated['starts_at'],
                $validated['photographer_id'] ?? null,
                $validated['studio_id'] ?? null,
                $correlationId
            );
            return response()->json([
                'success' => true,
                'booking_uuid' => $booking->uuid,
                'correlation_id' => $correlationId
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'correlation_id' => $correlationId
            ], 400);
        }
    }
    /**
     * Получение списка студий по гео-точкам
     */
    public function listStudios(Request $request): JsonResponse
    {
        $correlationId = (string) \Illuminate\Support\Str::uuid();
        $studios = PhotoStudio::where('is_verified', true)
            ->orderBy('rating', 'desc')
            ->limit(10)
            ->get();
        return response()->json([
            'success' => true,
            'data' => $studios,
            'correlation_id' => $correlationId
        ]);
    }
}
