<?php declare(strict_types=1);

namespace Tests\Unit\Domains\Education;

use Tests\TestCase;
use App\Domains\Education\DTOs\CalculatePriceDto;
use App\Domains\Education\DTOs\PriceAdjustmentDto;
use App\Domains\Education\Services\EducationDynamicPricingService;
use App\Domains\Education\Models\Course;
use App\Models\User;
use App\Services\FraudControlService;
use App\Services\AuditService;
use App\Services\Security\IdempotencyService;
use App\Services\ML\AnonymizationService;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\DB;
use Mockery;

final class EducationDynamicPricingServiceTest extends TestCase
{
    private EducationDynamicPricingService $service;
    private FraudControlService $fraud;
    private AuditService $audit;
    private IdempotencyService $idempotency;
    private AnonymizationService $anonymizer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->fraud = Mockery::mock(FraudControlService::class);
        $this->audit = Mockery::mock(AuditService::class);
        $this->idempotency = Mockery::mock(IdempotencyService::class);
        $this->anonymizer = Mockery::mock(AnonymizationService::class);

        $this->service = new EducationDynamicPricingService(
            $this->fraud,
            $this->audit,
            $this->idempotency,
            $this->anonymizer,
        );
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_calculate_dynamic_price_b2c(): void
    {
        $course = Course::factory()->create([
            'tenant_id' => tenant()->id,
            'price_kopecks' => 100000,
            'corporate_price_kopecks' => 80000,
        ]);

        $dto = new CalculatePriceDto(
            tenantId: tenant()->id,
            businessGroupId: null,
            courseId: $course->id,
            correlationId: 'test-correlation-123',
            idempotencyKey: null,
            isCorporate: false,
            userId: null,
            userSegment: null,
            enrollmentCount: null,
            timeSlot: null,
        );

        $this->fraud->shouldReceive('check')->once()->with($dto);
        $this->idempotency->shouldReceive('check')->once()->andReturn([]);
        $this->audit->shouldReceive('record')->once();
        $this->anonymizer->shouldReceive('anonymizeEvent')->once()->andReturn([]);

        Redis::shouldReceive('get')->once()->andReturn(null);
        Redis::shouldReceive('setex')->once();

        $result = $this->service->calculateDynamicPrice($dto);

        $this->assertInstanceOf(PriceAdjustmentDto::class, $result);
        $this->assertNotEmpty($result->priceId);
        $this->assertEquals(100000, $result->originalPriceKopecks);
        $this->assertIsArray($result->factors);
        $this->assertArrayHasKey('demand', $result->factors);
        $this->assertArrayHasKey('seasonality', $result->factors);
        $this->assertArrayHasKey('competition', $result->factors);
    }

    public function test_calculate_dynamic_price_b2b(): void
    {
        $course = Course::factory()->create([
            'tenant_id' => tenant()->id,
            'price_kopecks' => 100000,
            'corporate_price_kopecks' => 80000,
        ]);

        $dto = new CalculatePriceDto(
            tenantId: tenant()->id,
            businessGroupId: 1,
            courseId: $course->id,
            correlationId: 'test-correlation-456',
            idempotencyKey: null,
            isCorporate: true,
            userId: null,
            userSegment: null,
            enrollmentCount: null,
            timeSlot: null,
        );

        $this->fraud->shouldReceive('check')->once()->with($dto);
        $this->idempotency->shouldReceive('check')->once()->andReturn([]);
        $this->audit->shouldReceive('record')->once();
        $this->anonymizer->shouldReceive('anonymizeEvent')->once()->andReturn([]);

        Redis::shouldReceive('get')->once()->andReturn(null);
        Redis::shouldReceive('setex')->once();

        $result = $this->service->calculateDynamicPrice($dto);

        $this->assertEquals(80000, $result->originalPriceKopecks);
    }

    public function test_calculate_dynamic_price_with_user_segment_vip(): void
    {
        $course = Course::factory()->create([
            'tenant_id' => tenant()->id,
            'price_kopecks' => 100000,
        ]);

        $dto = new CalculatePriceDto(
            tenantId: tenant()->id,
            businessGroupId: null,
            courseId: $course->id,
            correlationId: 'test-correlation-789',
            idempotencyKey: null,
            isCorporate: false,
            userId: null,
            userSegment: 'vip',
            enrollmentCount: null,
            timeSlot: null,
        );

        $this->fraud->shouldReceive('check')->once()->with($dto);
        $this->idempotency->shouldReceive('check')->once()->andReturn([]);
        $this->audit->shouldReceive('record')->once();
        $this->anonymizer->shouldReceive('anonymizeEvent')->once()->andReturn([]);

        Redis::shouldReceive('get')->once()->andReturn(null);
        Redis::shouldReceive('setex')->once();

        $result = $this->service->calculateDynamicPrice($dto);

        $this->assertLessThan($result->originalPriceKopecks, $result->adjustedPriceKopecks);
    }

    public function test_calculate_dynamic_price_with_peak_hours(): void
    {
        $course = Course::factory()->create([
            'tenant_id' => tenant()->id,
            'price_kopecks' => 100000,
        ]);

        $dto = new CalculatePriceDto(
            tenantId: tenant()->id,
            businessGroupId: null,
            courseId: $course->id,
            correlationId: 'test-correlation-peak',
            idempotencyKey: null,
            isCorporate: false,
            userId: null,
            userSegment: null,
            enrollmentCount: null,
            timeSlot: '19:00',
        );

        $this->fraud->shouldReceive('check')->once()->with($dto);
        $this->idempotency->shouldReceive('check')->once()->andReturn([]);
        $this->audit->shouldReceive('record')->once();
        $this->anonymizer->shouldReceive('anonymizeEvent')->once()->andReturn([]);

        Redis::shouldReceive('get')->once()->andReturn(null);
        Redis::shouldReceive('setex')->once();

        $result = $this->service->calculateDynamicPrice($dto);

        $this->assertGreaterThan(0, $result->discountPercent);
    }

    public function test_trigger_flash_sale(): void
    {
        $course = Course::factory()->create([
            'tenant_id' => tenant()->id,
            'price_kopecks' => 100000,
        ]);

        $this->audit->shouldReceive('record')->once();

        $result = $this->service->triggerFlashSale(
            courseId: $course->id,
            discountPercent: 30,
            correlationId: 'flash-sale-correlation',
        );

        $this->assertInstanceOf(PriceAdjustmentDto::class, $result);
        $this->assertEquals(100000, $result->originalPriceKopecks);
        $this->assertEquals(70000, $result->adjustedPriceKopecks);
        $this->assertEquals(30, $result->discountPercent);
        $this->assertTrue($result->isFlashSale);
        $this->assertEquals('manual_flash_sale', $result->adjustmentReason);
    }

    public function test_calculate_dynamic_price_with_idempotency(): void
    {
        $course = Course::factory()->create([
            'tenant_id' => tenant()->id,
            'price_kopecks' => 100000,
        ]);

        $dto = new CalculatePriceDto(
            tenantId: tenant()->id,
            businessGroupId: null,
            courseId: $course->id,
            correlationId: 'test-correlation-idemp',
            idempotencyKey: 'unique-key-456',
            isCorporate: false,
            userId: null,
            userSegment: null,
            enrollmentCount: null,
            timeSlot: null,
        );

        $cachedResponse = [
            'price_id' => 'cached-price-123',
            'original_price_kopecks' => 100000,
            'adjusted_price_kopecks' => 90000,
            'discount_percent' => 10,
            'adjustment_reason' => 'test',
            'factors' => [],
            'valid_until' => now()->addHours(24)->toIso8601String(),
            'is_flash_sale' => false,
            'generated_at' => now()->toIso8601String(),
        ];

        $this->fraud->shouldReceive('check')->once()->with($dto);
        $this->idempotency->shouldReceive('check')
            ->once()
            ->with('education_pricing_calculation', 'unique-key-456', $dto->toArray(), tenant()->id)
            ->andReturn($cachedResponse);

        $result = $this->service->calculateDynamicPrice($dto);

        $this->assertInstanceOf(PriceAdjustmentDto::class, $result);
        $this->assertEquals('cached-price-123', $result->priceId);
    }
}
