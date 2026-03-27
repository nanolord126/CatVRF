<?php
declare(strict_types=1);
namespace App\Http\Controllers\Api\Cleaning;
use App\Services\Cleaning\CleaningBookingService;
use App\Services\Cleaning\AICleaningConstructor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;
/**
 * CleaningApiController.
 * Internal public API for the CleaningServices vertical.
 * Supporting B2B/B2C logic, AI analysis, and order lifecycle.
 */
final class CleaningApiController extends Controller
{
    /**
     * Constructor with dependency injection.
     */
    public function __construct(
        private readonly CleaningBookingService $bookingService,
        private readonly AICleaningConstructor $aiConstructor
    ) {}
    /**
     * AI Deep Analysis of cleaning area/photo.
     * Generates a recommended service plan.
     */
    public function analyzeArea(Request $request): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID', Str::uuid()->toString());
        try {
            $request->validate([
                'photo' => 'required|image|max:10240', // 10MB limit
                'budget' => 'nullable|integer|min:0',
                'is_commercial' => 'boolean',
            ]);
            $photo = $request->file('photo');
            $budget = (int) $request->get('budget', 500000); // 5000 Rub default
            $isCommercial = (bool) $request->get('is_commercial', false);
            $analysis = $this->aiConstructor->analyzePhotoAndMatchService(
                $photo,
                $budget,
                $isCommercial,
                $correlationId
            );
            return response()->json([
                'success' => true,
                'correlation_id' => $correlationId,
                'analysis' => $analysis,
                'offer_id' => Str::random(12),
            ]);
        } catch (Throwable $e) {
            Log::channel('audit')->error('[CleaningAPI] AI Analysis Failed', [
                'correlation_id' => $correlationId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json([
                'success' => false,
                'message' => 'AI Processing failed. Fallback to manual selection.',
                'error' => $e->getMessage(),
            ], 422);
        }
    }
    /**
     * Initialize Cleaning Booking.
     * Triggers fraud check, rate limiting, and 3rd party hold logic.
     */
    public function bookCleaning(Request $request): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID', Str::uuid()->toString());
        try {
            // Validation (Simplified for logic flow, usually via FormRequest)
            $data = $request->validate([
                'service_id' => 'required|integer|exists:cleaning_services,id',
                'user_id' => 'required|integer|exists:users,id',
                'scheduled_at' => 'required|date|after:now',
                'is_commercial' => 'boolean',
                'address' => 'required|string',
            ]);
            $order = $this->bookingService->createOrder($data, $correlationId);
            return response()->json([
                'success' => true,
                'correlation_id' => $correlationId,
                'order_uuid' => $order->uuid,
                'payment_link' => "/checkout/pay/{$order->uuid}", // Dummy link for system
                'message' => 'Order initialized. Please pay the 30% deposit.',
            ]);
        } catch (Throwable $e) {
            Log::channel('audit')->error('[CleaningAPI] Booking Failed', [
                'correlation_id' => $correlationId,
                'error' => $e->getMessage(),
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Cleaning reservation failed.',
                'error' => $e->getMessage(),
            ], 400);
        }
    }
    /**
     * Post-Cleaning Photo-Fix verification.
     * Completes the order workflow after visual confirmation by AI/Manager.
     */
    public function verifyAndComplete(Request $request, string $orderUuid): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID', Str::uuid()->toString());
        try {
            $request->validate([
                'after_photo' => 'required|image|max:10240',
            ]);
            $photo = $request->file('after_photo');
            $result = $this->bookingService->completeJob($orderUuid, $photo, $correlationId);
            return response()->json([
                'success' => $result,
                'correlation_id' => $correlationId,
                'message' => 'Cleaning verified. Order closed successfully.',
            ]);
        } catch (Throwable $e) {
            Log::channel('audit')->error('[CleaningAPI] Completion Verification Failed', [
                'order_uuid' => $orderUuid,
                'correlation_id' => $correlationId,
                'error' => $e->getMessage(),
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Verification failed. Cleaner might need to redo certain spots.',
                'error' => $e->getMessage(),
            ], 422);
        }
    }
}
