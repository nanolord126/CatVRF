<?php declare(strict_types=1);

namespace Tests\Unit\Modules\Taxi;

use Tests\TestCase;
use Modules\Taxi\Services\DynamicSurgePricingService;
use App\Services\AuditService;
use Illuminate\Support\Facades\Redis;
use Mockery;

final class DynamicSurgePricingServiceTest extends TestCase
{
    private DynamicSurgePricingService $service;
    private AuditService $audit;

    protected function setUp(): void
    {
        parent::setUp();

        $this->audit = Mockery::mock(AuditService::class);

        $this->service = new DynamicSurgePricingService(
            $this->audit,
        );
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_calculate_surge_multiplier(): void
    {
        Redis::shouldReceive('get')->once()->andReturn(null);
        Redis::shouldReceive('setex')->once();
        $this->audit->shouldReceive('record')->once();

        $result = $this->service->calculateSurgeMultiplier(55.7558, 37.6173, 'test-correlation');

        $this->assertArrayHasKey('multiplier', $result);
        $this->assertGreaterThanOrEqual(1.0, $result['multiplier']);
        $this->assertLessThanOrEqual(5.0, $result['multiplier']);
    }

    public function test_trigger_surge(): void
    {
        $this->audit->shouldReceive('record')->once();

        $this->service->triggerSurge(1, 2.5, 'High demand', 'test-correlation');
    }

    public function test_decay_surge(): void
    {
        $this->audit->shouldReceive('record')->once();

        $this->service->decaySurge(1, 'test-correlation');
    }
}
