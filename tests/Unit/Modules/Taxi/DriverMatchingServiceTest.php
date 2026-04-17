<?php declare(strict_types=1);

namespace Tests\Unit\Modules\Taxi;

use Tests\TestCase;
use Modules\Taxi\Services\DriverMatchingService;
use App\Services\AuditService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Mockery;

final class DriverMatchingServiceTest extends TestCase
{
    private DriverMatchingService $service;
    private AuditService $audit;

    protected function setUp(): void
    {
        parent::setUp();

        $this->audit = Mockery::mock(AuditService::class);

        $this->service = new DriverMatchingService(
            $this->audit,
        );
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_find_best_drivers(): void
    {
        Redis::shouldReceive('get')->once()->andReturn(null);
        Redis::shouldReceive('setex')->once();
        $this->audit->shouldReceive('record')->once();

        $result = $this->service->findBestDrivers(55.7558, 37.6173, 'economy', 1, 'test-correlation');

        $this->assertIsArray($result);
    }

    public function test_assign_driver(): void
    {
        DB::table('taxi_drivers')->insert([
            'id' => 1,
            'uuid' => 'driver-uuid-1',
            'name' => 'Test Driver',
            'status' => 'available',
            'is_online' => true,
            'is_verified' => true,
            'rating' => 4.5,
            'total_rides' => 100,
            'acceptance_rate' => 95,
            'current_streak' => 5,
        ]);

        DB::table('taxi_rides')->insert([
            'id' => 1,
            'uuid' => 'ride-uuid-1',
            'passenger_id' => 1,
            'status' => 'searching',
        ]);

        $this->audit->shouldReceive('record')->once();

        $this->service->assignDriver(1, 1, 'test-correlation');

        $driver = DB::table('taxi_drivers')->where('id', 1)->first();
        $this->assertEquals('busy', $driver->status);
    }

    public function test_release_driver(): void
    {
        DB::table('taxi_drivers')->insert([
            'id' => 1,
            'uuid' => 'driver-uuid-1',
            'name' => 'Test Driver',
            'status' => 'busy',
            'is_online' => true,
            'is_verified' => true,
            'rating' => 4.5,
            'total_rides' => 100,
            'acceptance_rate' => 95,
            'current_streak' => 5,
        ]);

        $this->audit->shouldReceive('record')->once();

        $this->service->releaseDriver(1, 'test-correlation');

        $driver = DB::table('taxi_drivers')->where('id', 1)->first();
        $this->assertEquals('available', $driver->status);
    }
}
