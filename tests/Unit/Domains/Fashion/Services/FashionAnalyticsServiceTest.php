<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\Fashion\Services;

use Modules\Fashion\Services\FashionAnalyticsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class FashionAnalyticsServiceTest extends TestCase
{
    use RefreshDatabase;

    private FashionAnalyticsService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(FashionAnalyticsService::class);
    }

    public function test_get_store_analytics(): void
    {
        $result = $this->service->getStoreAnalytics(1, 1);

        $this->assertIsArray($result);
    }

    public function test_get_product_analytics(): void
    {
        $result = $this->service->getProductAnalytics(1, 1);

        $this->assertIsArray($result);
    }

    public function test_get_store_analytics_with_date_range(): void
    {
        $startDate = now()->subDays(30);
        $endDate = now();

        $result = $this->service->getStoreAnalytics(1, 1, $startDate, $endDate);

        $this->assertIsArray($result);
    }

    public function test_analytics_includes_revenue(): void
    {
        $result = $this->service->getStoreAnalytics(1, 1);

        $this->assertArrayHasKey('revenue', $result);
    }

    public function test_analytics_includes_orders_count(): void
    {
        $result = $this->service->getStoreAnalytics(1, 1);

        $this->assertArrayHasKey('orders_count', $result);
    }

    public function test_analytics_includes_conversion_rate(): void
    {
        $result = $this->service->getStoreAnalytics(1, 1);

        $this->assertArrayHasKey('conversion_rate', $result);
    }
}
