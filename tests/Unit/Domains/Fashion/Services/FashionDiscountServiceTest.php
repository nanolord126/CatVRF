<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\Fashion\Services;

use Modules\Fashion\Services\FashionDiscountService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class FashionDiscountServiceTest extends TestCase
{
    use RefreshDatabase;

    private FashionDiscountService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(FashionDiscountService::class);
    }

    public function test_apply_coupon(): void
    {
        $result = $this->service->applyCoupon('SUMMER20', 1000, 1);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('success', $result);
        $this->assertArrayHasKey('discount_amount', $result);
    }

    public function test_apply_invalid_coupon(): void
    {
        $result = $this->service->applyCoupon('INVALID', 1000, 1);

        $this->assertArrayHasKey('success', $result);
        $this->assertFalse($result['success']);
    }

    public function test_create_flash_sale(): void
    {
        $result = $this->service->createFlashSale(1, [1, 2, 3], 20, now()->addHours(24), 1);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('success', $result);
    }

    public function test_get_active_flash_sales(): void
    {
        $result = $this->service->getActiveFlashSales(1);

        $this->assertIsArray($result);
    }

    public function test_validate_coupon(): void
    {
        $result = $this->service->validateCoupon('SUMMER20', 1);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('valid', $result);
    }

    public function test_calculate_discounted_price(): void
    {
        $result = $this->service->calculateDiscountedPrice(1000, 20);

        $this->assertEquals(800, $result);
    }

    public function test_get_product_discounts(): void
    {
        $result = $this->service->getProductDiscounts(1, 1);

        $this->assertIsArray($result);
    }

    public function test_apply_percentage_discount(): void
    {
        $result = $this->service->calculateDiscountedPrice(1000, 25);

        $this->assertEquals(750, $result);
    }
}
