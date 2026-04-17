<?php declare(strict_types=1);

namespace Modules\Taxi\Http\Controllers;

use Modules\Taxi\Services\TaxiRideService;
use Modules\Taxi\Services\AI\TaxiRouteAIConstructorService;
use Modules\Taxi\Requests\CreateTaxiRideRequest;
use Modules\Taxi\Resources\TaxiRideResource;
use Modules\Taxi\Models\TaxiRide;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * API Controller for Taxi Rides — Production Ready 2026.
 * 
 * Exposes HTTP endpoints for ride management.
 * Follows CatVRF 2026 canon: correlation_id, fraud hints, tenant isolation.
 */
final readonly class TaxiRideController
{
    public function __construct(
        private TaxiRideService $service,
        private TaxiRouteAIConstructorService $aiService,
    ) {}

    /**
     * Create a new taxi ride.
     */
    public function create(CreateTaxiRideRequest $request): JsonResponse
    {
        $dto = new \Modules\Taxi\Services\TaxiRideCreateDto(
            tenantId: $request->tenant_id,
            passengerId: $request->passenger_id,
            pickupLatitude: $request->pickup_latitude,
            pickupLongitude: $request->pickup_longitude,
            dropoffLatitude: $request->dropoff_latitude,
            dropoffLongitude: $request->dropoff_longitude,
            pickupAddress: $request->pickup_address,
            dropoffAddress: $request->dropoff_address,
            estimatedPriceKopeki: $request->estimated_price_kopeki,
            correlationId: $request->correlation_id,
            idempotencyKey: $request->idempotency_key,
            inn: $request->inn,
            businessCardId: $request->business_card_id,
            voiceOrder: $request->boolean('voice_order', false),
            biometricVerified: $request->boolean('biometric_verified', false),
            splitPayment: $request->boolean('split_payment', false),
            splitPaymentUsers: $request->split_payment_users ?? [],
            arNavigationEnabled: $request->boolean('ar_navigation_enabled', true),
            videoCallRequested: $request->boolean('video_call_requested', false),
            ipAddress: $request->ip(),
            deviceFingerprint: $request->header('X-Device-Fingerprint'),
        );

        $ride = $this->service->createRide($dto);

        return response()->json([
            'success' => true,
            'message' => 'Ride created successfully',
            'data' => TaxiRideResource::make($ride),
            'correlation_id' => $request->correlation_id,
        ], 201);
    }

    /**
     * Get ride details.
     */
    public function show(int $id, Request $request): JsonResponse
    {
        $ride = TaxiRide::where('id', $id)->firstOrFail();

        return response()->json([
            'success' => true,
            'data' => TaxiRideResource::make($ride),
            'correlation_id' => $request->header('X-Correlation-ID', Str::uuid()->toString()),
        ]);
    }

    /**
     * Match driver to ride.
     */
    public function matchDriver(int $id, Request $request): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID', Str::uuid()->toString());
        
        $ride = $this->service->matchDriver($id, $correlationId);

        return response()->json([
            'success' => true,
            'message' => 'Driver matched successfully',
            'data' => TaxiRideResource::make($ride),
            'correlation_id' => $correlationId,
        ]);
    }

    /**
     * Start ride.
     */
    public function startRide(int $id, Request $request): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID', Str::uuid()->toString());
        
        $ride = $this->service->startRide($id, $correlationId);

        return response()->json([
            'success' => true,
            'message' => 'Ride started successfully',
            'data' => TaxiRideResource::make($ride),
            'correlation_id' => $correlationId,
        ]);
    }

    /**
     * Complete ride.
     */
    public function completeRide(int $id, Request $request): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID', Str::uuid()->toString());
        
        $ride = $this->service->completeRide($id, $correlationId);

        return response()->json([
            'success' => true,
            'message' => 'Ride completed successfully',
            'data' => TaxiRideResource::make($ride),
            'correlation_id' => $correlationId,
        ]);
    }

    /**
     * Cancel ride.
     */
    public function cancelRide(int $id, Request $request): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID', Str::uuid()->toString);
        $reason = $request->input('reason', 'Cancelled by user');

        $ride = $this->service->cancelRide($id, $reason, $correlationId);

        return response()->json([
            'success' => true,
            'message' => 'Ride cancelled successfully',
            'data' => TaxiRideResource::make($ride),
            'correlation_id' => $correlationId,
        ]);
    }

    /**
     * Update driver location.
     */
    public function updateDriverLocation(int $driverId, Request $request): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID', Str::uuid()->toString());
        
        $request->validate([
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
        ]);

        $this->service->updateDriverLocation(
            driverId: $driverId,
            latitude: (float) $request->latitude,
            longitude: (float) $request->longitude,
            correlationId: $correlationId,
        );

        return response()->json([
            'success' => true,
            'message' => 'Location updated successfully',
            'correlation_id' => $correlationId,
        ]);
    }

    /**
     * Submit rating.
     */
    public function submitRating(int $id, Request $request): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID', Str::uuid()->toString());
        
        $request->validate([
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'rated_by' => ['required', 'in:passenger,driver'],
        ]);

        $ride = $this->service->submitRating(
            rideId: $id,
            rating: (int) $request->rating,
            ratedBy: $request->rated_by,
            correlationId: $correlationId,
        );

        return response()->json([
            'success' => true,
            'message' => 'Rating submitted successfully',
            'data' => TaxiRideResource::make($ride),
            'correlation_id' => $correlationId,
        ]);
    }

    /**
     * Analyze route and get AI recommendations.
     */
    public function analyzeRoute(Request $request): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID', Str::uuid()->toString());
        
        $request->validate([
            'pickup_latitude' => ['required', 'numeric', 'between:-90,90'],
            'pickup_longitude' => ['required', 'numeric', 'between:-180,180'],
            'dropoff_latitude' => ['required', 'numeric', 'between:-90,90'],
            'dropoff_longitude' => ['required', 'numeric', 'between:-180,180'],
            'user_id' => ['required', 'integer', 'min:1'],
            'vehicle_class' => ['nullable', 'in:economy,comfort,business,premium'],
        ]);

        $result = $this->aiService->analyzeRouteAndRecommend(
            pickupLat: (float) $request->pickup_latitude,
            pickupLon: (float) $request->pickup_longitude,
            dropoffLat: (float) $request->dropoff_latitude,
            dropoffLon: (float) $request->dropoff_longitude,
            userId: (int) $request->user_id,
            correlationId: $correlationId,
            vehicleClass: $request->vehicle_class,
        );

        return response()->json([
            'success' => true,
            'data' => $result,
            'correlation_id' => $correlationId,
        ]);
    }

    /**
     * Predict surge pricing.
     */
    public function predictSurge(Request $request): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID', Str::uuid()->toString());
        
        $request->validate([
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
        ]);

        $result = $this->aiService->predictSurgePricing(
            latitude: (float) $request->latitude,
            longitude: (float) $request->longitude,
            correlationId: $correlationId,
        );

        return response()->json([
            'success' => true,
            'data' => $result,
            'correlation_id' => $correlationId,
        ]);
    }

    /**
     * Analyze driver behavior.
     */
    public function analyzeDriver(int $driverId, Request $request): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID', Str::uuid()->toString());
        
        $result = $this->aiService->analyzeDriverBehavior($driverId, $correlationId);

        return response()->json([
            'success' => true,
            'data' => $result,
            'correlation_id' => $correlationId,
        ]);
    }
}
