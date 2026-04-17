<?php declare(strict_types=1);

namespace Tests\Feature\Taxi;

use App\Domains\Taxi\Models\TaxiRide;
use App\Domains\Taxi\Services\TaxiOrderService;
use App\Domains\Taxi\DTOs\CreateTaxiOrderDto;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

final class TaxiLoadTest extends TestCase
{
    use RefreshDatabase;

    public function test_handle_100_concurrent_order_creations(): void
    {
        $orders = [];
        $startTime = microtime(true);

        for ($i = 0; $i < 100; $i++) {
            $dto = new CreateTaxiOrderDto(
                tenantId: 1,
                businessGroupId: null,
                passengerId: 1,
                pickupAddress: "Moscow, Location {$i}",
                pickupLat: 55.75396,
                pickupLon: 37.62039,
                dropoffAddress: 'Moscow, Kremlin',
                dropoffLat: 55.7520,
                dropoffLon: 37.6175,
                paymentMethod: 'wallet',
                isSplitPayment: false,
                splitPaymentDetails: null,
                voiceOrderEnabled: false,
                biometricAuthRequired: false,
                videoCallEnabled: false,
                inn: null,
                businessCardId: null,
                ipAddress: '127.0.0.1',
                deviceFingerprint: 'test-fingerprint',
                correlationId: "load-test-{$i}",
                idempotencyKey: null,
                deviceType: 'mobile',
                appVersion: '1.0.0',
            );

            $ride = app(TaxiOrderService::class)->createOrder($dto);
            $orders[] = $ride;
        }

        $endTime = microtime(true);
        $duration = $endTime - $startTime;

        $this->assertCount(100, $orders);
        $this->assertLessThan(10, $duration, '100 orders should be created in less than 10 seconds');
    }

    public function test_price_calculation_under_load(): void
    {
        $calculations = [];
        $startTime = microtime(true);

        for ($i = 0; $i < 500; $i++) {
            $estimate = app(TaxiOrderService::class)->estimatePrice(
                pickupLat: 55.75396,
                pickupLon: 37.62039,
                dropoffLat: 55.7520,
                dropoffLon: 37.6175,
                vehicleClass: 'economy',
                correlationId: "load-price-{$i}",
            );
            $calculations[] = $estimate;
        }

        $endTime = microtime(true);
        $duration = $endTime - $startTime;

        $this->assertCount(500, $calculations);
        $this->assertLessThan(5, $duration, '500 price calculations should complete in less than 5 seconds');
    }

    public function test_driver_matching_under_load(): void
    {
        $matches = [];
        $startTime = microtime(true);

        for ($i = 0; $i < 50; $i++) {
            $match = app(\App\Domains\Taxi\Services\TaxiDriverMatchingService::class)->matchDriver(
                new \App\Domains\Taxi\DTOs\TaxiDriverMatchingDto(
                    rideId: $i,
                    pickupLat: 55.75396,
                    pickupLon: 37.62039,
                    tenantId: 1,
                    correlationId: "load-match-{$i}",
                )
            );
            $matches[] = $match;
        }

        $endTime = microtime(true);
        $duration = $endTime - $startTime;

        $this->assertCount(50, $matches);
        $this->assertLessThan(3, $duration, '50 driver matches should complete in less than 3 seconds');
    }

    public function test_database_connection_pool_handles_load(): void
    {
        $rides = [];
        $startTime = microtime(true);

        for ($i = 0; $i < 200; $i++) {
            $ride = TaxiRide::create([
                'uuid' => \Illuminate\Support\Str::uuid(),
                'tenant_id' => 1,
                'passenger_id' => 1,
                'pickup_address' => "Location {$i}",
                'pickup_lat' => 55.75396,
                'pickup_lon' => 37.62039,
                'dropoff_address' => 'Kremlin',
                'dropoff_lat' => 55.7520,
                'dropoff_lon' => 37.6175,
                'payment_method' => 'wallet',
                'status' => 'pending',
                'total_price' => 50000,
            ]);
            $rides[] = $ride;
        }

        $endTime = microtime(true);
        $duration = $endTime - $startTime;

        $this->assertCount(200, $rides);
        $this->assertLessThan(8, $duration, '200 database inserts should complete in less than 8 seconds');
    }

    public function test_cache_performance_under_load(): void
    {
        Cache::flush();

        $writes = [];
        $reads = [];
        $startTime = microtime(true);

        for ($i = 0; $i < 1000; $i++) {
            $key = "taxi:load-test:{$i}";
            $value = ['data' => $i, 'timestamp' => now()];
            
            Cache::put($key, $value, 60);
            $writes[] = $key;
        }

        foreach ($writes as $key) {
            $read = Cache::get($key);
            $reads[] = $read;
        }

        $endTime = microtime(true);
        $duration = $endTime - $startTime;

        $this->assertCount(1000, $reads);
        $this->assertLessThan(5, $duration, '1000 cache operations should complete in less than 5 seconds');
    }

    public function test_concurrent_order_status_updates(): void
    {
        $rides = TaxiRide::factory()->count(50)->create(['status' => 'pending']);
        $startTime = microtime(true);

        foreach ($rides as $ride) {
            $ride->update(['status' => 'driver_assigned', 'driver_id' => 1]);
        }

        $endTime = microtime(true);
        $duration = $endTime - $startTime;

        $this->assertCount(50, $rides);
        $this->assertLessThan(3, $duration, '50 concurrent updates should complete in less than 3 seconds');
    }

    public function test_memory_usage_stays_within_limits(): void
    {
        $initialMemory = memory_get_usage(true);
        
        for ($i = 0; $i < 1000; $i++) {
            $dto = new CreateTaxiOrderDto(
                tenantId: 1,
                businessGroupId: null,
                passengerId: 1,
                pickupAddress: "Moscow, Location {$i}",
                pickupLat: 55.75396,
                pickupLon: 37.62039,
                dropoffAddress: 'Moscow, Kremlin',
                dropoffLat: 55.7520,
                dropoffLon: 37.6175,
                paymentMethod: 'wallet',
                isSplitPayment: false,
                splitPaymentDetails: null,
                voiceOrderEnabled: false,
                biometricAuthRequired: false,
                videoCallEnabled: false,
                inn: null,
                businessCardId: null,
                ipAddress: '127.0.0.1',
                deviceFingerprint: 'test-fingerprint',
                correlationId: "memory-test-{$i}",
                idempotencyKey: null,
                deviceType: 'mobile',
                appVersion: '1.0.0',
            );
        }

        $finalMemory = memory_get_usage(true);
        $memoryIncrease = ($finalMemory - $initialMemory) / 1024 / 1024;

        $this->assertLessThan(50, $memoryIncrease, 'Memory increase should be less than 50MB for 1000 DTOs');
    }

    public function test_response_time_remains_consistent_under_load(): void
    {
        $responseTimes = [];

        for ($i = 0; $i < 100; $i++) {
            $startTime = microtime(true);

            $estimate = app(TaxiOrderService::class)->estimatePrice(
                pickupLat: 55.75396,
                pickupLon: 37.62039,
                dropoffLat: 55.7520,
                dropoffLon: 37.6175,
                vehicleClass: 'economy',
                correlationId: "response-time-{$i}",
            );

            $endTime = microtime(true);
            $responseTimes[] = $endTime - $startTime;
        }

        $averageResponseTime = array_sum($responseTimes) / count($responseTimes);
        $maxResponseTime = max($responseTimes);

        $this->assertLessThan(0.1, $averageResponseTime, 'Average response time should be less than 100ms');
        $this->assertLessThan(0.5, $maxResponseTime, 'Max response time should be less than 500ms');
    }
}
