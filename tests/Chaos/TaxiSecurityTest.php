<?php declare(strict_types=1);

namespace Tests\Chaos;

use Tests\TestCase;
use Modules\Taxi\Services\TaxiRideService;
use Modules\Taxi\Services\TaxiRideCreateDto;
use Modules\Taxi\Models\TaxiRide;
use Modules\Taxi\Models\TaxiDriver;
use App\Services\FraudControlService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;

final class TaxiSecurityTest extends TestCase
{
    use RefreshDatabase;

    private TaxiRideService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(TaxiRideService::class);
    }

    public function test_fake_ride_blocked_by_fraud(): void
    {
        $dto = new TaxiRideCreateDto(
            tenantId: 1,
            passengerId: 999999,
            pickupLatitude: 55.7558,
            pickupLongitude: 37.6173,
            dropoffLatitude: 55.7520,
            dropoffLongitude: 37.6150,
            pickupAddress: 'Fake Address',
            dropoffAddress: 'Fake Destination',
            estimatedPriceKopeki: 15000,
            correlationId: 'fake-ride-test',
            ipAddress: '192.168.1.100',
            deviceFingerprint: 'suspicious-fingerprint',
        );

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('blocked by fraud detection');
        
        $this->service->createRide($dto);
    }

    public function test_driver_impersonation_prevented(): void
    {
        $maliciousDriver = TaxiDriver::factory()->create([
            'is_verified' => false,
            'is_active' => false,
            'status' => TaxiDriver::STATUS_BANNED,
        ]);

        $ride = TaxiRide::factory()->create([
            'status' => TaxiRide::STATUS_REQUESTED,
        ]);

        $this->expectException(\RuntimeException::class);
        
        $this->service->matchDriver($ride->id, 'impersonation-test');
    }

    public function test_spam_ride_creation_rate_limited(): void
    {
        $spamCount = 0;
        for ($i = 0; $i < 20; $i++) {
            try {
                $dto = new TaxiRideCreateDto(
                    tenantId: 1,
                    passengerId: 1,
                    pickupLatitude: 55.7558,
                    pickupLongitude: 37.6173,
                    dropoffLatitude: 55.7520,
                    dropoffLongitude: 37.6150,
                    pickupAddress: 'Test',
                    dropoffAddress: 'Test',
                    estimatedPriceKopeki: 15000,
                    correlationId: "spam-test-{$i}",
                );
                $this->service->createRide($dto);
                $spamCount++;
            } catch (\RuntimeException $e) {
                break;
            }
        }

        $this->assertLessThan(20, $spamCount);
    }

    public function test_fraudulent_payment_attempt_blocked(): void
    {
        $driver = TaxiDriver::factory()->create();
        $ride = TaxiRide::factory()->create([
            'status' => TaxiRide::STATUS_STARTED,
            'driver_id' => $driver->id,
            'final_price_kopeki' => 999999999,
        ]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('blocked by fraud detection');
        
        $this->service->completeRide($ride->id, 'fraud-payment-test');
    }

    public function test_price_manipulation_prevented(): void
    {
        $dto = new TaxiRideCreateDto(
            tenantId: 1,
            passengerId: 1,
            pickupLatitude: 55.7558,
            pickupLongitude: 37.6173,
            dropoffLatitude: 55.7520,
            dropoffLongitude: 37.6150,
            pickupAddress: 'Test',
            dropoffAddress: 'Test',
            estimatedPriceKopeki: 999999999,
            correlationId: 'price-manipulation-test',
        );

        $ride = $this->service->createRide($dto);
        
        $this->assertLessThan(999999999, $ride->final_price_kopeki);
    }

    public function test_idempotency_prevents_duplicate_rides(): void
    {
        $dto1 = new TaxiRideCreateDto(
            tenantId: 1,
            passengerId: 1,
            pickupLatitude: 55.7558,
            pickupLongitude: 37.6173,
            dropoffLatitude: 55.7520,
            dropoffLongitude: 37.6150,
            pickupAddress: 'Test',
            dropoffAddress: 'Test',
            estimatedPriceKopeki: 15000,
            correlationId: 'idempotency-test',
            idempotencyKey: 'unique-key-123',
        );

        $dto2 = new TaxiRideCreateDto(
            tenantId: 1,
            passengerId: 1,
            pickupLatitude: 55.7558,
            pickupLongitude: 37.6173,
            dropoffLatitude: 55.7520,
            dropoffLongitude: 37.6150,
            pickupAddress: 'Test',
            dropoffAddress: 'Test',
            estimatedPriceKopeki: 15000,
            correlationId: 'idempotency-test',
            idempotencyKey: 'unique-key-123',
        );

        $ride1 = $this->service->createRide($dto1);
        $ride2 = $this->service->createRide($dto2);

        $this->assertEquals($ride1->id, $ride2->id);
    }

    public function test_tenant_isolation_enforced(): void
    {
        $dtoTenant1 = new TaxiRideCreateDto(
            tenantId: 1,
            passengerId: 1,
            pickupLatitude: 55.7558,
            pickupLongitude: 37.6173,
            dropoffLatitude: 55.7520,
            dropoffLongitude: 37.6150,
            pickupAddress: 'Tenant 1',
            dropoffAddress: 'Test',
            estimatedPriceKopeki: 15000,
            correlationId: 'tenant-1-test',
        );

        $dtoTenant2 = new TaxiRideCreateDto(
            tenantId: 2,
            passengerId: 1,
            pickupLatitude: 55.7558,
            pickupLongitude: 37.6173,
            dropoffLatitude: 55.7520,
            dropoffLongitude: 37.6150,
            pickupAddress: 'Tenant 2',
            dropoffAddress: 'Test',
            estimatedPriceKopeki: 15000,
            correlationId: 'tenant-2-test',
        );

        $ride1 = $this->service->createRide($dtoTenant1);
        $ride2 = $this->service->createRide($dtoTenant2);

        $this->assertNotEquals($ride1->tenant_id, $ride2->tenant_id);
    }
}
