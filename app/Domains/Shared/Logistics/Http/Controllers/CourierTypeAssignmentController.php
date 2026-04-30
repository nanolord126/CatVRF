<?php

declare(strict_types=1);

namespace App\Domains\Logistics\Http\Controllers;

use Illuminate\Contracts\Validation\Factory as ValidatorFactory;

use App\Domains\Logistics\Services\CourierFitPredictionService;
use App\Domains\Logistics\Services\TimeWindowValidator;
use App\Domains\Logistics\Services\UnifiedFleetService;
use App\Services\FraudControlService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Psr\Log\LoggerInterface;
use App\Domains\Logistics\Models\Courier;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Str;

final readonly class CourierTypeAssignmentController
{
    public function __construct(private readonly ValidatorFactory $validatorFactory,
        private readonly UnifiedFleetService $fleetService,
        private readonly TimeWindowValidator $timeWindowValidator,
        private readonly CourierFitPredictionService $predictionService,
        private readonly FraudControlService $fraudControlService,
        private readonly LoggerInterface $logger,) {}

    /**
     * Assign courier using multi-type strategy.
     */
    public function assign(Request $request): JsonResponse
    {
        $validator = $this->validatorFactory->make($request->all(), [
            'tenant_id' => 'required|integer',
            'weight_kg' => 'required|numeric|min:0',
            'pickup_lat' => 'required|numeric|between:-90,90',
            'pickup_lng' => 'required|numeric|between:-180,180',
            'delivery_lat' => 'required|numeric|between:-90,90',
            'delivery_lng' => 'required|numeric|between:-180,180',
            'city' => 'nullable|string|max:100',
            'weather_conditions' => 'nullable|array',
            'weather_conditions.*' => 'string',
            'use_ml' => 'nullable|boolean',
            'shadow_mode' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return new JsonResponse([
                'error' => 'Validation failed',
                'messages' => $validator->errors(),
            ], 422);
        }

        $validated = $validator->validated();
        $correlationId = $request->header('X-Correlation-ID') ?? (string) Str::uuid();

        // Fraud check
        $this->fraudControlService->check(
            userId: $request->user()?->id ?? 0,
            operationType: 'courier_assignment_multi_type',
            amount: 0,
            correlationId: $correlationId,
        );

        try {
            $courier = $this->fleetService->assignCourierMultiType(
                tenantId: (int) $validated['tenant_id'],
                weightKg: (float) $validated['weight_kg'],
                pickupLocation: ['lat' => (float) $validated['pickup_lat'], 'lng' => (float) $validated['pickup_lng']],
                deliveryLocation: ['lat' => (float) $validated['delivery_lat'], 'lng' => (float) $validated['delivery_lng']],
                city: $validated['city'] ?? null,
                weatherConditions: $validated['weather_conditions'] ?? [],
                useML: $validated['use_ml'] ?? false,
                shadowMode: $validated['shadow_mode'] ?? false,
                correlationId: $correlationId,
            );

            if ($courier === null) {
                return new JsonResponse([
                    'error' => 'No available couriers',
                    'message' => 'No couriers available for the given constraints',
                ], 404);
            }

            return new JsonResponse([
                'courier_id' => $courier->id,
                'courier_uuid' => $courier->uuid,
                'vehicle_type' => $courier->vehicle_type,
                'correlation_id' => $correlationId,
            ]);
        } catch (\Throwable $e) {
            $this->logger->error('Courier assignment failed', [
                'error' => $e->getMessage(),
                'correlation_id' => $correlationId,
            ]);

            return new JsonResponse([
                'error' => 'Assignment failed',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Calculate delivery cost using multi-type strategy.
     */
    public function calculateCost(Request $request): JsonResponse
    {
        $validator = $this->validatorFactory->make($request->all(), [
            'tenant_id' => 'required|integer',
            'weight_kg' => 'required|numeric|min:0',
            'pickup_lat' => 'required|numeric|between:-90,90',
            'pickup_lng' => 'required|numeric|between:-180,180',
            'delivery_lat' => 'required|numeric|between:-90,90',
            'delivery_lng' => 'required|numeric|between:-180,180',
            'city' => 'nullable|string|max:100',
            'weather_conditions' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return new JsonResponse([
                'error' => 'Validation failed',
                'messages' => $validator->errors(),
            ], 422);
        }

        $validated = $validator->validated();

        try {
            $costKopek = $this->fleetService->calculateDeliveryCostMultiType(
                tenantId: (int) $validated['tenant_id'],
                weightKg: (float) $validated['weight_kg'],
                pickupLocation: ['lat' => (float) $validated['pickup_lat'], 'lng' => (float) $validated['pickup_lng']],
                deliveryLocation: ['lat' => (float) $validated['delivery_lat'], 'lng' => (float) $validated['delivery_lng']],
                city: $validated['city'] ?? null,
                weatherConditions: $validated['weather_conditions'] ?? [],
            );

            return new JsonResponse([
                'cost_kopek' => $costKopek,
                'cost_rubles' => $costKopek / 100,
            ]);
        } catch (\Throwable $e) {
            $this->logger->error('Cost calculation failed', [
                'error' => $e->getMessage(),
            ]);

            return new JsonResponse([
                'error' => 'Cost calculation failed',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Validate time window for delivery.
     */
    public function validateTimeWindow(Request $request): JsonResponse
    {
        $validator = $this->validatorFactory->make($request->all(), [
            'tenant_id' => 'required|integer',
            'type' => 'required|string|in:pedestrian,scooter,ebike,car,taxi',
            'distance_km' => 'required|numeric|min:0',
            'time_window_start' => 'required|string|date_format:H:i',
            'time_window_end' => 'required|string|date_format:H:i',
            'city' => 'nullable|string|max:100',
            'buffer_min' => 'nullable|integer|min:0',
        ]);

        if ($validator->fails()) {
            return new JsonResponse([
                'error' => 'Validation failed',
                'messages' => $validator->errors(),
            ], 422);
        }

        $validated = $validator->validated();

        try {
            $canMeet = $this->timeWindowValidator->canMeetTimeWindow(
                tenantId: (int) $validated['tenant_id'],
                type: $validated['type'],
                distanceKm: (float) $validated['distance_km'],
                timeWindowStart: $validated['time_window_start'],
                timeWindowEnd: $validated['time_window_end'],
                city: $validated['city'] ?? null,
                bufferMin: (int) ($validated['buffer_min'] ?? 15),
            );

            return new JsonResponse([
                'can_meet' => $canMeet,
            ]);
        } catch (\Throwable $e) {
            $this->logger->error('Time window validation failed', [
                'error' => $e->getMessage(),
            ]);

            return new JsonResponse([
                'error' => 'Validation failed',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get best courier type for time window.
     */
    public function getBestTypeForWindow(Request $request): JsonResponse
    {
        $validator = $this->validatorFactory->make($request->all(), [
            'tenant_id' => 'required|integer',
            'distance_km' => 'required|numeric|min:0',
            'time_window_start' => 'required|string|date_format:H:i',
            'time_window_end' => 'required|string|date_format:H:i',
            'city' => 'nullable|string|max:100',
            'buffer_min' => 'nullable|integer|min:0',
        ]);

        if ($validator->fails()) {
            return new JsonResponse([
                'error' => 'Validation failed',
                'messages' => $validator->errors(),
            ], 422);
        }

        $validated = $validator->validated();

        try {
            $bestType = $this->timeWindowValidator->getBestTypeForTimeWindow(
                tenantId: (int) $validated['tenant_id'],
                distanceKm: (float) $validated['distance_km'],
                timeWindowStart: $validated['time_window_start'],
                timeWindowEnd: $validated['time_window_end'],
                city: $validated['city'] ?? null,
                bufferMin: (int) ($validated['buffer_min'] ?? 15),
            );

            if ($bestType === null) {
                return new JsonResponse([
                    'error' => 'No suitable type found',
                    'message' => 'No courier type can meet the time window',
                ], 404);
            }

            return new JsonResponse($bestType);
        } catch (\Throwable $e) {
            $this->logger->error('Best type lookup failed', [
                'error' => $e->getMessage(),
            ]);

            return new JsonResponse([
                'error' => 'Lookup failed',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Predict ETA for courier.
     */
    public function predictEta(Request $request): JsonResponse
    {
        $validator = $this->validatorFactory->make($request->all(), [
            'courier_id' => 'required|integer',
            'pickup_lat' => 'required|numeric|between:-90,90',
            'pickup_lng' => 'required|numeric|between:-180,180',
            'delivery_lat' => 'required|numeric|between:-90,90',
            'delivery_lng' => 'required|numeric|between:-180,180',
            'weight_kg' => 'required|numeric|min:0',
            'city' => 'nullable|string|max:100',
        ]);

        if ($validator->fails()) {
            return new JsonResponse([
                'error' => 'Validation failed',
                'messages' => $validator->errors(),
            ], 422);
        }

        $validated = $validator->validated();

        try {
            $courier = Courier::findOrFail((int) $validated['courier_id']);

            $etaMin = $this->predictionService->predictETA(
                courier: $courier,
                pickupLocation: ['lat' => (float) $validated['pickup_lat'], 'lng' => (float) $validated['pickup_lng']],
                deliveryLocation: ['lat' => (float) $validated['delivery_lat'], 'lng' => (float) $validated['delivery_lng']],
                weightKg: (float) $validated['weight_kg'],
                tenantId: $courier->tenant_id,
                city: $validated['city'] ?? null,
            );

            return new JsonResponse([
                'eta_min' => $etaMin,
                'courier_id' => $courier->id,
                'vehicle_type' => $courier->vehicle_type,
            ]);
        } catch (ModelNotFoundException $e) {
            return new JsonResponse([
                'error' => 'Courier not found',
            ], 404);
        } catch (\Throwable $e) {
            $this->logger->error('ETA prediction failed', [
                'error' => $e->getMessage(),
            ]);

            return new JsonResponse([
                'error' => 'Prediction failed',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get recommended time slots.
     */
    public function getRecommendedSlots(Request $request): JsonResponse
    {
        $validator = $this->validatorFactory->make($request->all(), [
            'tenant_id' => 'required|integer',
            'type' => 'required|string|in:pedestrian,scooter,ebike,car,taxi',
            'distance_km' => 'required|numeric|min:0',
            'city' => 'nullable|string|max:100',
            'slot_duration_min' => 'nullable|integer|min:15',
            'slots_ahead' => 'nullable|integer|min:1|max:20',
        ]);

        if ($validator->fails()) {
            return new JsonResponse([
                'error' => 'Validation failed',
                'messages' => $validator->errors(),
            ], 422);
        }

        $validated = $validator->validated();

        try {
            $slots = $this->timeWindowValidator->getRecommendedTimeSlots(
                tenantId: (int) $validated['tenant_id'],
                type: $validated['type'],
                distanceKm: (float) $validated['distance_km'],
                city: $validated['city'] ?? null,
                slotDurationMin: (int) ($validated['slot_duration_min'] ?? 60),
                slotsAhead: (int) ($validated['slots_ahead'] ?? 5),
            );

            return new JsonResponse([
                'slots' => $slots,
            ]);
        } catch (\Throwable $e) {
            $this->logger->error('Slot recommendation failed', [
                'error' => $e->getMessage(),
            ]);

            return new JsonResponse([
                'error' => 'Recommendation failed',
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
