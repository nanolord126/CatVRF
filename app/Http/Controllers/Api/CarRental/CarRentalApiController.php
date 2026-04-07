<?php declare(strict_types=1);

namespace App\Http\Controllers\Api\CarRental;

use App\Http\Controllers\Controller;
use Illuminate\Log\LogManager;
use Illuminate\Contracts\Routing\ResponseFactory;

final class CarRentalApiController extends Controller
{

    public function __construct(
            private readonly CarRentalBookingService $bookingService,
            private readonly AICarRecommendationConstructor $aiConstructor,
            private readonly LogManager $logger,
            private readonly ResponseFactory $response,
    ) {}
        /**
         * Endpoint: GET /api/car-rental/fleet
         * Browsing available fleet within tenant.
         */
        public function getFleet(Request $request): JsonResponse
        {
            $correlationId = $request->header('X-Correlation-ID', (string) Str::uuid());
            // 1. Fetch available cars with eager loading
            $cars = Car::where('status', 'available')
                ->with(['type', 'company'])
                ->latest()
                ->paginate($request->get('limit', 15));
            // 2. Audit Log (Canon 2026: Traceable access)
            $this->logger->channel('audit')->info('[CarRentalAPI] Fleet requested', [
                'correlation_id' => $correlationId,
                'client_ip' => $request->ip(),
                'count' => $cars->count(),
            ]);
            return $this->response->json([
                'status' => 'success',
                'correlation_id' => $correlationId,
                'data' => $cars,
            ]);
        }
        /**
         * Endpoint: POST /api/car-rental/recommend
         * Artificial Intelligence matching logic (Layer 7: AI/ML).
         */
        public function recommend(Request $request): JsonResponse
        {
            $correlationId = $request->header('X-Correlation-ID', (string) Str::uuid());
            // 1. Validation (Canon Rule: Strict input check)
            $validated = $request->validate([
                'person_count' => 'required|integer|min:1|max:10',
                'travel_goal' => 'required|string|max:500',
                'budget_limit' => 'nullable|integer',
                'days' => 'required|integer|min:1',
                'is_b2b' => 'boolean',
            ]);
            try {
                // 2. AI Matching Logic (Layer 7: Analysis Layer)
                $recommendations = $this->aiConstructor->analyzeAndMatchVehicle(
                    personCount: $validated['person_count'],
                    travelGoal: $validated['travel_goal'],
                    budgetLimit: $validated['budget_limit'] ?? 1000000,
                    days: $validated['days'],
                    isB2B: $validated['is_b2b'] ?? false
                );
                // 3. Audit Log
                $this->logger->channel('audit')->info('[CarRentalAPI] AI Recommendations generated', [
                    'correlation_id' => $correlationId,
                    'person_count' => $validated['person_count'],
                    'travel_goal_summary' => Str::limit($validated['travel_goal'], 30),
                ]);
                return $this->response->json([
                    'status' => 'success',
                    'correlation_id' => $correlationId,
                    'recommendations' => $recommendations,
                ]);
            } catch (\Throwable $e) {
                $this->logger->error("[CarRentalAPI] AI Matching Failed", [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
                return $this->response->json([
                    'status' => 'error',
                    'message' => 'AI reasoning failed',
                    'correlation_id' => $correlationId,
                ], 500);
            }
        }
        /**
         * Endpoint: GET /api/car-rental/car/{id}
         * Detailed vehicle info for frontend.
         */
        public function getCarDetails(int $id): JsonResponse
        {
            $car = Car::with(['type', 'company'])->findOrFail($id);
            return $this->response->json([
                'status' => 'success',
                'data' => $car,
            ]);
        }
}
