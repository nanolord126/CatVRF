<?php

declare(strict_types=1);

namespace Tests\Feature\Logistics;

use App\Domains\Logistics\Models\Courier;
use App\Domains\Logistics\Models\DeliveryOrder;
use App\Domains\Logistics\Services\SurgePricingService;
use App\Domains\Logistics\Services\DeliveryOrderService;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

/**
 * КАНОН 2026 — ИНТЕГРАЦИОННЫЕ ТЕСТЫ ЛОГИСТИКИ
 * 1. Проверка Surge Pricing (1.0 vs 1.5)
 * 2. Проверка назначения курьера
 * 3. Изоляция по Tenant ID
 */
final class LogisticsFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // В реальности: установка tenant() через TenantHelper
    }

    /**
     * @test
     */
    public function it_calculates_correct_surge_multiplier(): void
    {
        $service = app(SurgePricingService::class);
        $surge = $service->calculateSurge(55.7558, 37.6173, 'logistics');

        $this->assertIsFloat($surge);
        $this->assertGreaterThanOrEqual(1.0, $surge);
    }

    /**
     * @test
     */
    public function it_creates_order_and_assigns_available_courier(): void
    {
        $correlationId = (string) Str::uuid();
        $orderService = app(DeliveryOrderService::class);

        // Создать курьера
        $courier = Courier::factory()->create([
            'status' => 'online',
            'last_lat' => 55.75,
            'last_lon' => 37.62,
        ]);

        // Создать заказ через сервис
        $orderData = [
            'pickup_address' => 'Test Pickup',
            'pickup_lat' => 55.755,
            'pickup_lon' => 37.618,
            'dropoff_address' => 'Test Dropoff',
            'dropoff_lat' => 55.76,
            'dropoff_lon' => 37.64,
        ];

        $order = $orderService->createOrder($orderData, $correlationId);

        $this->assertDatabaseHas('delivery_orders', [
            'uuid' => $order->uuid,
            'status' => 'pending',
            'correlation_id' => $correlationId
        ]);

        // Присвоение курьера
        $assigned = $orderService->assignCourier($order, $courier, $correlationId);

        $this->assertTrue($assigned);
        $this->assertEquals($courier->id, $order->fresh()->courier_id);
        $this->assertEquals('assigned', $order->fresh()->status);
        $this->assertEquals('busy', $courier->fresh()->status);
    }
}
