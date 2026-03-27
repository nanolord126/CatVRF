<?php
declare(strict_types=1);
namespace App\Http\Controllers\Api\Psychology;
use App\Domains\Medical\Psychology\Models\Psychologist;
use App\Domains\Medical\Psychology\Services\PsychologicalService;
use App\Domains\Medical\Psychology\Services\AI\AITherapyConstructorService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
/**
 * API для работы с психологическими услугами.
 */
final class PsychologicalApiController extends Controller
{
    public function __construct(
        private readonly PsychologicalService $service,
        private readonly AITherapyConstructorService $aiService,
    ) {}
    /**
     * Поиск психологов.
     */
    public function index(Request $request): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID', (string) Str::uuid());
        Log::channel('audit')->info('API: Fetching psychologists', [
            'correlation_id' => $correlationId,
        ]);
        $psychologists = Psychologist::with(['clinic', 'reviews'])
            ->where('is_available', true)
            ->paginate($request->integer('per_page', 15));
        return response()->json([
            'data' => $psychologists,
            'correlation_id' => $correlationId,
        ]);
    }
    /**
     * AI-подбор программы.
     */
    public function aiMatch(Request $request): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID', (string) Str::uuid());
        $request->validate([
            'symptoms' => 'required|array',
            'min_exp' => 'nullable|integer',
        ]);
        $matches = $this->aiService->generateTherapyPlan($request->all(), $correlationId);
        return response()->json([
            'plan' => $matches,
            'correlation_id' => $correlationId,
        ]);
    }
    /**
     * Запись на сессию.
     */
    public function storeBooking(Request $request): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID', (string) Str::uuid());
        $data = $request->validate([
            'psychologist_id' => 'required|exists:psychologists,id',
            'service_id' => 'required|exists:psy_services,id',
            'scheduled_at' => 'required|date|after:now',
            'client_notes' => 'nullable|string',
        ]);
        try {
            $booking = $this->service->createBooking(array_merge($data, [
                'client_id' => auth()->id() ?? 1, // Fallback for demo
                'price_at_booking' => 5000, // Placeholder price logic
            ]), $correlationId);
            return response()->json([
                'success' => true,
                'booking_uuid' => $booking->uuid,
                'correlation_id' => $correlationId,
            ], 201);
        } catch (\Throwable $e) {
            Log::channel('audit')->error('API Booking Failed', [
                'error' => $e->getMessage(),
                'correlation_id' => $correlationId,
            ]);
            return response()->json([
                'error' => $e->getMessage(),
                'correlation_id' => $correlationId,
            ], 400);
        }
    }
}
