<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\Beauty\Services;

use App\Domains\Beauty\DTOs\DynamicPricingDto;
use App\Domains\Beauty\Services\DynamicPricingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class DynamicPricingServiceTest extends TestCase
{
    use RefreshDatabase;

    private DynamicPricingService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(DynamicPricingService::class);
    }

    public function test_calculate_dynamic_pricing(): void
    {
        $dto = new DynamicPricingDto(
            tenantId: 1,
            businessGroupId: null,
            masterId: 1,
            serviceId: 1,
            timeSlot: null,
            basePrice: 1000,
            correlationId: 'test-correlation',
        );

        $result = $this->service->calculate($dto);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('success', $result);
        $this->assertArrayHasKey('final_price', $result);
        $this->assertArrayHasKey('surge_multiplier', $result);
        $this->assertTrue($result['success']);
    }

    public function test_surge_pricing_applied(): void
    {
        $dto = new DynamicPricingDto(
            tenantId: 1,
            businessGroupId: null,
            masterId: 1,
            serviceId: 1,
            timeSlot: now()->addHours(2)->toIso8601String(),
            basePrice: 1000,
            correlationId: 'test-correlation',
        );

        $result = $this->service->calculate($dto);

        $this->assertArrayHasKey('is_surge_pricing', $result);
    }

    public function test_b2b_discount_applied(): void
    {
        $dto = new DynamicPricingDto(
            tenantId: 1,
            businessGroupId: 1,
            masterId: 1,
            serviceId: 1,
            timeSlot: null,
            basePrice: 1000,
            correlationId: 'test-correlation',
            isB2B: true,
        );

        $result = $this->service->calculate($dto);

        $this->assertArrayHasKey('final_price', $result);
    }
}
