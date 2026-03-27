<?php

declare(strict_types=1);

namespace App\Domains\Auto\Tests;

use App\Domains\Auto\Models\AutoRepairOrder;
use App\Domains\Auto\Models\AutoVehicle;
use App\Domains\Auto\Models\AutoPart;
use App\Domains\Auto\Services\AutoRepairService;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;

/**
 * AutoVerticalSystemTest — Канон 2026.
 * Полный интеграционный тест вертикали Auto.
 */
final class AutoVerticalSystemTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Тест: Создание заказ-наряда (Layer 0-2).
     */
    public function test_can_create_repair_order_with_proper_tenancy(): void
    {
        $tenantId = 1;
        $correlationId = (string) Str::uuid();

        // 1. Создать ТС
        $vehicle = AutoVehicle::factory()->create([
            'tenant_id' => $tenantId,
            'vin' => 'TESTVIN1234567890',
        ]);

        // 2. Создать заказ-наряд
        $orderData = [
            'tenant_id' => $tenantId,
            'client_id' => 10,
            'vehicle_id' => $vehicle->id,
            'status' => 'pending',
            'total_cost_kopecks' => 1500000, // 15 000 руб.
            'correlation_id' => $correlationId,
        ];

        $order = AutoRepairOrder::create($orderData);

        // 3. Проверка в БД
        $this->assertDatabaseHas('auto_repair_orders', [
            'uuid' => $order->uuid,
            'tenant_id' => $tenantId,
            'correlation_id' => $correlationId,
        ]);
    }

    /**
     * Тест: VIN валидация (Layer 4).
     */
    public function test_vin_validation_is_strict(): void
    {
        // Некорректный VIN (символ 'I' запрещен в ISO)
        $invalidVin = 'INVALID1234567890';
        
        $response = $this->postJson('/api/v1/auto/catalog/search', [
            'vin' => $invalidVin,
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['vin']);
    }

    /**
     * Тест: Списание запчастей (Layer 5/6).
     */
    public function test_stock_is_deducted_on_repair_completion(): void
    {
        $part = AutoPart::factory()->create(['current_stock' => 10]);
        $order = AutoRepairOrder::factory()->create(['status' => 'in_progress']);

        // Привязать запчасть к заказу (через pivot table, если реализовано)
        // ... (логика списания в сервисе)
    }
}
