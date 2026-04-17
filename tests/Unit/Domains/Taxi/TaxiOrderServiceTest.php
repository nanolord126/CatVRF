<?php declare(strict_types=1);

namespace Tests\Unit\Domains\Taxi;

use App\Domains\Taxi\DTOs\CreateTaxiOrderDto;
use App\Domains\Taxi\Models\TaxiRide;
use App\Domains\Taxi\Services\TaxiOrderService;
use App\Services\FraudControlService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

final class TaxiOrderServiceTest extends TestCase
{
    use RefreshDatabase;

    private readonly TaxiOrderService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(TaxiOrderService::class);
    }

    public function test_create_order_successfully(): void
    {
        $dto = new CreateTaxiOrderDto(
            passengerId: 1,
            pickupAddress: 'Moscow, Red Square',
            pickupLat: 55.75396,
            pickupLon: 37.62039,
            dropoffAddress: 'Moscow, Kremlin',
            dropoffLat: 55.7520,
            dropoffLon: 37.6175,
            paymentMethod: 'wallet',
            isSplitPayment: false,
            splitPaymentDetails: [],
            voiceOrderEnabled: false,
            biometricAuthRequired: false,
            videoCallEnabled: false,
            inn: null,
            businessCardId: null,
            deviceType: 'mobile',
            appVersion: '1.0.0',
            tenantId: 1,
            businessGroupId: null,
            ipAddress: '127.0.0.1',
            deviceFingerprint: 'test-fingerprint',
            userId: 1,
            correlationId: 'test-correlation-123',
        );

        $ride = $this->service->createOrder($dto);

        $this->assertInstanceOf(TaxiRide::class, $ride);
        $this->assertEquals('pending', $ride->status);
        $this->assertEquals(1, $ride->passenger_id);
        $this->assertEquals('Moscow, Red Square', $ride->pickup_address);
        $this->assertEquals('wallet', $ride->payment_method);
        $this->assertDatabaseHas('taxi_rides', [
            'uuid' => $ride->uuid,
            'passenger_id' => 1,
            'status' => 'pending',
        ]);
    }

    public function test_create_order_with_split_payment(): void
    {
        $dto = new CreateTaxiOrderDto(
            passengerId: 1,
            pickupAddress: 'Moscow, Red Square',
            pickupLat: 55.75396,
            pickupLon: 37.62039,
            dropoffAddress: 'Moscow, Kremlin',
            dropoffLat: 55.7520,
            dropoffLon: 37.6175,
            paymentMethod: 'wallet',
            isSplitPayment: true,
            splitPaymentDetails: [
                ['user_id' => 1, 'share' => 50],
                ['user_id' => 2, 'share' => 50],
            ],
            voiceOrderEnabled: false,
            biometricAuthRequired: false,
            videoCallEnabled: false,
            inn: null,
            businessCardId: null,
            deviceType: 'mobile',
            appVersion: '1.0.0',
            tenantId: 1,
            businessGroupId: null,
            ipAddress: '127.0.0.1',
            userId: 1,
            deviceFingerprint: 'test-fingerprint',
            correlationId: 'test-correlation-123',
        );

        $ride = $this->service->createOrder($dto);

        $this->assertTrue($ride->is_split_payment);
        $this->assertIsArray($ride->split_payment_details);
        $this->assertCount(2, $ride->split_payment_details);
    }

    public function test_get_order_by_uuid(): void
    {
        $ride = TaxiRide::factory()->create([
            'uuid' => 'test-uuid-123',
            'passenger_id' => 1,
            'status' => 'pending',
        ]);

        $foundRide = $this->service->getOrder('test-uuid-123', 'test-correlation');

        $this->assertEquals($ride->id, $foundRide->id);
        $this->assertEquals('test-uuid-123', $foundRide->uuid);
    }

    public function test_cancel_order_successfully(): void
    {
        $ride = TaxiRide::factory()->create([
            'status' => 'pending',
            'passenger_id' => 1,
        ]);

        $result = $this->service->cancelRide($ride->id, 'User cancelled', 1, 'test-correlation');

        $this->assertTrue($result);
        $this->assertDatabaseHas('taxi_rides', [
            'id' => $ride->id,
            'status' => 'cancelled',
            'cancellation_reason' => 'User cancelled',
        ]);
    }

    public function test_rate_order_updates_driver_rating(): void
    {
        $ride = TaxiRide::factory()->create([
            'status' => 'completed',
            'driver_id' => 1,
            'passenger_id' => 2,
        ]);

        $this->service->rateOrder(
            rideUuid: $ride->uuid,
            driverRating: 5,
            passengerRating: 4,
            comment: 'Great ride',
            correlationId: 'test-correlation',
        );

        $this->assertDatabaseHas('taxi_rides', [
            'id' => $ride->id,
            'driver_rating' => 5,
            'passenger_rating' => 4,
            'rating_comment' => 'Great ride',
        ]);
    }

    public function test_estimate_price_returns_correct_structure(): void
    {
        $estimate = $this->service->estimatePrice(
            pickupLat: 55.75396,
            pickupLon: 37.62039,
            dropoffLat: 55.7520,
            dropoffLon: 37.6175,
            vehicleClass: 'economy',
            correlationId: 'test-correlation',
        );

        $this->assertIsArray($estimate);
        $this->assertArrayHasKey('distance_km', $estimate);
        $this->assertArrayHasKey('estimated_minutes', $estimate);
        $this->assertArrayHasKey('base_price', $estimate);
        $this->assertArrayHasKey('surge_multiplier', $estimate);
        $this->assertArrayHasKey('total_price', $estimate);
        $this->assertArrayHasKey('currency', $estimate);
        $this->assertEquals('RUB', $estimate['currency']);
    }

    public function test_get_user_orders_returns_paginated_results(): void
    {
        TaxiRide::factory()->count(5)->create(['passenger_id' => 1]);
        TaxiRide::factory()->count(3)->create(['passenger_id' => 2]);

        $orders = $this->service->getUserOrders(userId: 1, limit: 10, offset: 0, correlationId: 'test');

        $this->assertCount(5, $orders);
        $orders->each(function ($order) {
            $this->assertEquals(1, $order->passenger_id);
        });
    }
}
