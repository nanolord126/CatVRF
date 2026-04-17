<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\Fashion\Services;

use Modules\Fashion\Services\FashionInventoryManagementService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class FashionInventoryManagementServiceTest extends TestCase
{
    use RefreshDatabase;

    private FashionInventoryManagementService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(FashionInventoryManagementService::class);
    }

    public function test_reserve_stock(): void
    {
        $result = $this->service->reserveStock(1, 1, 5, 'order_123', 1);

        $this->assertIsBool($result);
    }

    public function test_release_stock(): void
    {
        $result = $this->service->releaseStock(1, 1, 5, 'order_123', 1);

        $this->assertIsBool($result);
    }

    public function test_confirm_stock(): void
    {
        $result = $this->service->confirmStock(1, 1, 5, 'order_123', 1);

        $this->assertIsBool($result);
    }

    public function test_get_low_stock_products(): void
    {
        $result = $this->service->getLowStockProducts(1, 10);

        $this->assertIsArray($result);
    }

    public function test_get_stock_forecast(): void
    {
        $result = $this->service->getStockForecast(1, 1, 30);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('forecasted_stock', $result);
        $this->assertArrayHasKey('reorder_date', $result);
    }

    public function test_get_inventory_report(): void
    {
        $result = $this->service->getInventoryReport(1);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('total_products', $result);
        $this->assertArrayHasKey('total_stock', $result);
    }

    public function test_reserve_stock_insufficient_quantity(): void
    {
        $result = $this->service->reserveStock(1, 1, 999999, 'order_123', 1);

        $this->assertFalse($result);
    }

    public function test_inventory_report_includes_low_stock_count(): void
    {
        $result = $this->service->getInventoryReport(1);

        $this->assertArrayHasKey('low_stock_count', $result);
    }
}
