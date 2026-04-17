<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\Sports;

use App\Domains\Sports\Services\SportsDynamicPricingService;
use App\Domains\Sports\Models\Gym;
use App\Services\FraudControlService;
use App\Services\AuditService;
use Illuminate\Database\DatabaseManager;
use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Redis\Connections\Connection as RedisConnection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class SportsDynamicPricingServiceTest extends TestCase
{
    use RefreshDatabase;

    private SportsDynamicPricingService $service;
    private FraudControlService $fraud;
    private AuditService $audit;
    private DatabaseManager $db;
    private Cache $cache;
    private RedisConnection $redis;

    protected function setUp(): void
    {
        parent::setUp();

        $this->fraud = $this->createMock(FraudControlService::class);
        $this->audit = $this->createMock(AuditService::class);
        $this->db = $this->app->make(DatabaseManager::class);
        $this->cache = $this->app->make(Cache::class);
        $this->redis = $this->app->make('redis');

        $this->service = new SportsDynamicPricingService(
            fraud: $this->fraud,
            audit: $this->audit,
            db: $this->db,
            cache: $this->cache,
            logger: $this->app->make('log'),
            redis: $this->redis,
        );
    }

    public function test_calculate_dynamic_price_success(): void
    {
        $this->fraud->expects($this->once())
            ->method('check')
            ->with(
                $this->equalTo(0),
                $this->equalTo('dynamic_pricing_calculation'),
                $this->equalTo(0),
                $this->equalTo('test-correlation-id')
            );

        $gym = Gym::create([
            'uuid' => \Illuminate\Support\Str::uuid()->toString(),
            'tenant_id' => 1,
            'business_group_id' => null,
            'name' => 'Test Gym',
            'address' => 'Test Address',
            'single_visit_price' => 500,
            'monthly_membership_price' => 3000,
            'personal_training_price' => 1500,
            'group_class_price' => 500,
            'max_daily_capacity' => 200,
            'is_active' => true,
        ]);

        $result = $this->service->calculateDynamicPrice($gym->id, 'single_visit', false, 0, 'test-correlation-id');

        $this->assertArrayHasKey('base_price', $result);
        $this->assertArrayHasKey('final_price', $result);
        $this->assertArrayHasKey('load_factor', $result);
        $this->assertArrayHasKey('time_multiplier', $result);
        $this->assertArrayHasKey('is_flash_discount', $result);
        $this->assertArrayHasKey('is_b2b', $result);
        $this->assertEquals(500, $result['base_price']);
        $this->assertFalse($result['is_b2b']);
    }

    public function test_calculate_dynamic_price_b2b(): void
    {
        $this->fraud->expects($this->once())
            ->method('check');

        $gym = Gym::create([
            'uuid' => \Illuminate\Support\Str::uuid()->toString(),
            'tenant_id' => 1,
            'business_group_id' => null,
            'name' => 'Test Gym',
            'address' => 'Test Address',
            'single_visit_price' => 500,
            'monthly_membership_price' => 3000,
            'personal_training_price' => 1500,
            'group_class_price' => 500,
            'max_daily_capacity' => 200,
            'is_active' => true,
        ]);

        $result = $this->service->calculateDynamicPrice($gym->id, 'single_visit', true, 1, 'test-correlation-id');

        $this->assertTrue($result['is_b2b']);
        $this->assertGreaterThan(0, $result['b2b_discount']);
        $this->assertLessThan($result['base_price'], $result['final_price']);
    }

    public function test_create_flash_membership_success(): void
    {
        $this->fraud->expects($this->once())
            ->method('check');

        $gym = Gym::create([
            'uuid' => \Illuminate\Support\Str::uuid()->toString(),
            'tenant_id' => 1,
            'business_group_id' => null,
            'name' => 'Test Gym',
            'address' => 'Test Address',
            'single_visit_price' => 500,
            'monthly_membership_price' => 3000,
            'personal_training_price' => 1500,
            'group_class_price' => 500,
            'max_daily_capacity' => 200,
            'is_active' => true,
        ]);

        $membershipData = [
            'tenant_id' => 1,
            'business_group_id' => null,
            'membership_type' => 'monthly',
            'duration_days' => 30,
            'base_price' => 3000,
            'amount' => 2100,
        ];

        $result = $this->service->createFlashMembership($gym->id, 1, $membershipData, 'test-correlation-id');

        $this->assertInstanceOf(\App\Domains\Sports\Models\Membership::class, $result);
        $this->assertTrue($result->is_flash);
        $this->assertEquals(2100, $result->discounted_price);
        $this->assertEquals(30, $result->discount_percentage);
    }

    public function test_create_flash_membership_not_available(): void
    {
        $this->fraud->expects($this->once())
            ->method('check');

        $gym = Gym::create([
            'uuid' => \Illuminate\Support\Str::uuid()->toString(),
            'tenant_id' => 1,
            'business_group_id' => null,
            'name' => 'Test Gym',
            'address' => 'Test Address',
            'single_visit_price' => 500,
            'monthly_membership_price' => 3000,
            'personal_training_price' => 1500,
            'group_class_price' => 500,
            'max_daily_capacity' => 200,
            'is_active' => true,
        ]);

        $membershipData = [
            'tenant_id' => 1,
            'business_group_id' => null,
            'membership_type' => 'monthly',
            'duration_days' => 30,
            'base_price' => 3000,
            'amount' => 3000,
        ];

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Flash membership discount is not available');

        $this->service->createFlashMembership($gym->id, 1, $membershipData, 'test-correlation-id');
    }

    public function test_get_bulk_membership_pricing_success(): void
    {
        $this->fraud->expects($this->once())
            ->method('check');

        $gym = Gym::create([
            'uuid' => \Illuminate\Support\Str::uuid()->toString(),
            'tenant_id' => 1,
            'business_group_id' => null,
            'name' => 'Test Gym',
            'address' => 'Test Address',
            'single_visit_price' => 500,
            'monthly_membership_price' => 3000,
            'personal_training_price' => 1500,
            'group_class_price' => 500,
            'max_daily_capacity' => 200,
            'is_active' => true,
        ]);

        $result = $this->service->getBulkMembershipPricing($gym->id, 50, 0, 'test-correlation-id');

        $this->assertArrayHasKey('employee_count', $result);
        $this->assertEquals(50, $result['employee_count']);
        $this->assertArrayHasKey('bulk_discount_percentage', $result);
        $this->assertArrayHasKey('total_price', $result);
        $this->assertArrayHasKey('savings', $result);
        $this->assertGreaterThan(0, $result['savings']);
    }

    public function test_update_pricing_based_on_load_success(): void
    {
        $gym = Gym::create([
            'uuid' => \Illuminate\Support\Str::uuid()->toString(),
            'tenant_id' => 1,
            'business_group_id' => null,
            'name' => 'Test Gym',
            'address' => 'Test Address',
            'single_visit_price' => 500,
            'monthly_membership_price' => 3000,
            'personal_training_price' => 1500,
            'group_class_price' => 500,
            'max_daily_capacity' => 200,
            'is_active' => true,
        ]);

        $this->service->updatePricingBasedOnLoad($gym->id, 'test-correlation-id');

        $pricingKey = "sports:pricing:{$gym->id}";
        $pricingData = json_decode($this->redis->get($pricingKey), true);

        $this->assertArrayHasKey('load_factor', $pricingData);
        $this->assertArrayHasKey('updated_at', $pricingData);

        $this->redis->del($pricingKey);
    }
}
