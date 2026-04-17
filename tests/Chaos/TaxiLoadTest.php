<?php declare(strict_types=1);

namespace Tests\Chaos;

use Tests\TestCase;
use Modules\Taxi\Services\TaxiRideService;
use Modules\Taxi\Services\TaxiRideCreateDto;
use Modules\Taxi\Models\TaxiRide;
use Modules\Taxi\Models\TaxiDriver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

final class TaxiLoadTest extends TestCase
{
    use RefreshDatabase;

    private TaxiRideService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(TaxiRideService::class);
    }

    public function test_concurrent_ride_creation(): void
    {
        TaxiDriver::factory()->count(50)->create(['status' => TaxiDriver::STATUS_AVAILABLE, 'is_active' => true]);

        $rides = [];
        for ($i = 0; $i < 100; $i++) {
            $dto = new TaxiRideCreateDto(
                tenantId: 1,
                passengerId: 1,
                pickupLatitude: 55.7558 + ($i * 0.0001),
                pickupLongitude: 37.6173 + ($i * 0.0001),
                dropoffLatitude: 55.7520 + ($i * 0.0001),
                dropoffLongitude: 37.6150 + ($i * 0.0001),
                pickupAddress: "Test {$i}",
                dropoffAddress: "Test {$i}",
                estimatedPriceKopeki: 15000,
                correlationId: "load-test-{$i}",
                idempotencyKey: "load-key-{$i}",
            );

            $rides[] = $this->service->createRide($dto);
        }

        $this->assertCount(100, $rides);
    }

    public function test_high_concurrency_driver_matching(): void
    {
        $drivers = TaxiDriver::factory()->count(20)->create(['status' => TaxiDriver::STATUS_AVAILABLE, 'is_active' => true]);
        $rides = TaxiRide::factory()->count(50)->create(['status' => TaxiRide::STATUS_REQUESTED]);

        $matchedCount = 0;
        foreach ($rides as $ride) {
            try {
                $this->service->matchDriver($ride->id, "concurrent-match-{$ride->id}");
                $matchedCount++;
            } catch (\RuntimeException $e) {
                continue;
            }
        }

        $this->assertGreaterThan(0, $matchedCount);
    }

    public function test_database_transaction_rollback_on_failure(): void
    {
        TaxiDriver::factory()->create(['status' => TaxiDriver::STATUS_AVAILABLE, 'is_active' => true]);

        $initialCount = TaxiRide::count();

        $dto = new TaxiRideCreateDto(
            tenantId: 1,
            passengerId: 999999,
            pickupLatitude: 55.7558,
            pickupLongitude: 37.6173,
            dropoffLatitude: 55.7520,
            dropoffLongitude: 37.6150,
            pickupAddress: 'Test',
            dropoffAddress: 'Test',
            estimatedPriceKopeki: 15000,
            correlationId: 'transaction-test',
        );

        try {
            $this->service->createRide($dto);
        } catch (\RuntimeException $e) {
        }

        $this->assertEquals($initialCount, TaxiRide::count());
    }

    public function test_memory_efficiency_bulk_operations(): void
    {
        $initialMemory = memory_get_usage();

        for ($i = 0; $i < 1000; $i++) {
            TaxiRide::factory()->create([
                'status' => TaxiRide::STATUS_COMPLETED,
                'final_price_kopeki' => 15000,
            ]);
        }

        $memoryUsed = memory_get_usage() - $initialMemory;
        $memoryLimit = 50 * 1024 * 1024;

        $this->assertLessThan($memoryLimit, $memoryUsed);
    }

    public function test_cache_performance_under_load(): void
    {
        $startTime = microtime(true);

        for ($i = 0; $i < 100; $i++) {
            Cache::remember("test-cache-{$i}", 60, fn() => ['data' => $i]);
            Cache::get("test-cache-{$i}");
        }

        $duration = microtime(true) - $startTime;

        $this->assertLessThan(1.0, $duration);
    }
}
