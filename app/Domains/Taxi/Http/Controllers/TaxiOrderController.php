<?php declare(strict_types=1);

namespace App\Domains\Taxi\Http\Controllers;

use App\Domains\Taxi\DTOs\CreateTaxiOrderDto;
use App\Domains\Taxi\Http\Requests\CreateTaxiOrderRequest;
use App\Domains\Taxi\Http\Requests\UpdateTaxiOrderRequest;
use App\Domains\Taxi\Resources\TaxiRideResource;
use App\Domains\Taxi\Services\TaxiOrderService;
use App\Services\FraudControlService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Psr\Log\LoggerInterface;

final readonly class TaxiOrderController
{
    public function __construct(
        private readonly TaxiOrderService $taxiOrderService,
        private readonly FraudControlService $fraud,
        private readonly LoggerInterface $logger,
    ) {}

    public function createOrder(CreateTaxiOrderRequest $request): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID', '');
        
        $this->fraud->check(
            userId: $request->user()->id ?? 0,
            operationType: 'taxi_order_create',
            amount: 0,
            ipAddress: $request->ip(),
            deviceFingerprint: $request->header('X-Device-Fingerprint'),
            correlationId: $correlationId,
        );

        $dto = new CreateTaxiOrderDto(
            passengerId: $request->user()->id,
            pickupAddress: $request->input('pickup_address'),
            pickupLat: (float) $request->input('pickup_lat'),
            pickupLon: (float) $request->input('pickup_lon'),
            dropoffAddress: $request->input('dropoff_address'),
            dropoffLat: (float) $request->input('dropoff_lat'),
            dropoffLon: (float) $request->input('dropoff_lon'),
            paymentMethod: $request->input('payment_method'),
            isSplitPayment: (bool) $request->input('is_split_payment', false),
            splitPaymentDetails: $request->input('split_payment_details', []),
            voiceOrderEnabled: (bool) $request->input('voice_order_enabled', false),
            biometricAuthRequired: (bool) $request->input('biometric_auth_required', false),
            videoCallEnabled: (bool) $request->input('video_call_enabled', false),
            inn: $request->input('inn'),
            businessCardId: $request->input('business_card_id'),
            deviceType: $request->input('device_type'),
            appVersion: $request->input('app_version'),
            tenantId: tenant()->id,
            businessGroupId: $request->user()->business_group_id,
            ipAddress: $request->ip(),
            deviceFingerprint: $request->header('X-Device-Fingerprint'),
            correlationId: $correlationId,
        );

        $ride = $this->taxiOrderService->createOrder($dto);

        $this->logger->info('Taxi order created', [
            'ride_id' => $ride->id,
            'passenger_id' => $request->user()->id,
            'correlation_id' => $correlationId,
        ]);

        return response()->json([
            'success' => true,
            'data' => new TaxiRideResource($ride),
            'correlation_id' => $correlationId,
        ], 201);
    }

    public function getOrder(string $rideUuid, Request $request): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID', '');

        $ride = $this->taxiOrderService->getOrder($rideUuid, $correlationId);

        return response()->json([
            'success' => true,
            'data' => new TaxiRideResource($ride),
            'correlation_id' => $correlationId,
        ]);
    }

    public function updateOrder(string $rideUuid, UpdateTaxiOrderRequest $request): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID', '');

        $this->fraud->check(
            userId: $request->user()->id ?? 0,
            operationType: 'taxi_order_update',
            amount: 0,
            ipAddress: $request->ip(),
            deviceFingerprint: $request->header('X-Device-Fingerprint'),
            correlationId: $correlationId,
        );

        $ride = $this->taxiOrderService->updateOrder(
            rideUuid: $rideUuid,
            status: $request->input('status'),
            driverId: $request->input('driver_id'),
            vehicleId: $request->input('vehicle_id'),
            actualDistanceKm: $request->input('actual_distance_km'),
            finalPrice: $request->input('final_price'),
            driverRating: $request->input('driver_rating'),
            passengerRating: $request->input('passenger_rating'),
            ratingComment: $request->input('rating_comment'),
            cancellationReason: $request->input('cancellation_reason'),
            cancellationFee: $request->input('cancellation_fee'),
            correlationId: $correlationId,
        );

        return response()->json([
            'success' => true,
            'data' => new TaxiRideResource($ride),
            'correlation_id' => $correlationId,
        ]);
    }

    public function cancelOrder(string $rideUuid, Request $request): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID', '');
        $reason = $request->input('reason', 'User cancelled');

        $this->fraud->check(
            userId: $request->user()->id ?? 0,
            operationType: 'taxi_order_cancel',
            amount: 0,
            ipAddress: $request->ip(),
            deviceFingerprint: $request->header('X-Device-Fingerprint'),
            correlationId: $correlationId,
        );

        $ride = $this->taxiOrderService->cancelOrder(
            rideUuid: $rideUuid,
            cancelledBy: 'passenger',
            reason: $reason,
            correlationId: $correlationId,
        );

        $this->logger->info('Taxi order cancelled', [
            'ride_id' => $ride->id,
            'passenger_id' => $request->user()->id,
            'reason' => $reason,
            'correlation_id' => $correlationId,
        ]);

        return response()->json([
            'success' => true,
            'data' => new TaxiRideResource($ride),
            'correlation_id' => $correlationId,
        ]);
    }

    public function rateOrder(string $rideUuid, Request $request): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID', '');

        $this->fraud->check(
            userId: $request->user()->id ?? 0,
            operationType: 'taxi_order_rate',
            amount: 0,
            ipAddress: $request->ip(),
            deviceFingerprint: $request->header('X-Device-Fingerprint'),
            correlationId: $correlationId,
        );

        $ride = $this->taxiOrderService->rateOrder(
            rideUuid: $rideUuid,
            driverRating: (int) $request->input('driver_rating'),
            passengerRating: (int) $request->input('passenger_rating'),
            comment: $request->input('comment'),
            correlationId: $correlationId,
        );

        return response()->json([
            'success' => true,
            'data' => new TaxiRideResource($ride),
            'correlation_id' => $correlationId,
        ]);
    }

    public function getUserOrders(Request $request): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID', '');
        $limit = (int) $request->input('limit', 20);
        $offset = (int) $request->input('offset', 0);

        $orders = $this->taxiOrderService->getUserOrders(
            userId: $request->user()->id,
            limit: $limit,
            offset: $offset,
            correlationId: $correlationId,
        );

        return response()->json([
            'success' => true,
            'data' => TaxiRideResource::collection($orders),
            'correlation_id' => $correlationId,
        ]);
    }

    public function estimatePrice(Request $request): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID', '');

        $this->fraud->check(
            userId: $request->user()->id ?? 0,
            operationType: 'taxi_price_estimate',
            amount: 0,
            ipAddress: $request->ip(),
            deviceFingerprint: $request->header('X-Device-Fingerprint'),
            correlationId: $correlationId,
        );

        $estimate = $this->taxiOrderService->estimatePrice(
            pickupLat: (float) $request->input('pickup_lat'),
            pickupLon: (float) $request->input('pickup_lon'),
            dropoffLat: (float) $request->input('dropoff_lat'),
            dropoffLon: (float) $request->input('dropoff_lon'),
            vehicleClass: $request->input('vehicle_class', 'economy'),
            correlationId: $correlationId,
        );

        return response()->json([
            'success' => true,
            'data' => $estimate,
            'correlation_id' => $correlationId,
        ]);
    }
}
