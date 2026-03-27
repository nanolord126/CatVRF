<?php

declare(strict_types=1);

namespace App\Domains\HouseholdGoods\HomeAppliance\Tests;

use App\Domains\HouseholdGoods\HomeAppliance\Models\ApplianceRepairOrder;
use App\Domains\HouseholdGoods\HomeAppliance\Models\AppliancePart;
use App\Domains\HouseholdGoods\HomeAppliance\Services\ApplianceRepairService;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

/**
 * HomeApplianceSystemTest — Канон 2026.
 * Интеграционный тест всего цикла ремонта: от заявки до гарантии.
 */
class HomeApplianceSystemTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Тест полного цикла ремонта: Создание -> Выбор запчастей -> Завершение -> Гарантия.
     */
    public function test_full_repair_lifecycle_with_warranty(): void
    {
        // 1. Arrange (Подготовка данных)
        $tenantId = 1;
        $correlationId = (string) Str::uuid();
        
        // Создаем запчасть (ТЭН для стиральной машины)
        $part = AppliancePart::create([
            'tenant_id' => $tenantId,
            'name' => 'ТЭН 2000W Bosch',
            'sku' => 'BOSCH-TEN-2000',
            'price_kopecks' => 250000, // 2500 руб.
            'stock_quantity' => 10,
            'correlation_id' => $correlationId
        ]);

        // Создаем заказ на ремонт
        $order = ApplianceRepairOrder::create([
            'tenant_id' => $tenantId,
            'client_id' => 10,
            'appliance_type' => 'washing_machine',
            'brand_name' => 'Bosch',
            'issue_description' => 'Не греет воду, ошибка F19',
            'address_json' => ['city' => 'Москва', 'street' => 'Тверская', 'house' => '1'],
            'correlation_id' => $correlationId
        ]);

        // 2. Act (Выполнение действий через сервис)
        $service = app(ApplianceRepairService::class, ['correlationId' => $correlationId]);
        
        // Начинаем ремонт с использованием запчасти
        $service->startRepair($order, [['part_id' => $part->id, 'quantity' => 1]], 150000); // 1500 руб работа

        // Проверяем списание со склада
        $part->refresh();
        $this->assertEquals(9, $part->stock_quantity);

        // Завершаем ремонт
        $service->completeRepair($order);

        // 3. Assert (Проверка результатов по Канону 2026)
        $order->refresh();
        $this->assertEquals('completed', $order->status);
        $this->assertEquals(400000, $order->total_cost_kopecks); // 2500 + 1500 = 4000 коп (4000 руб)
        
        // Проверка гарантии (180 дней для B2C)
        $this->assertNotNull($order->warranty_expires_at);
        $this->assertTrue($order->warranty_expires_at->isAfter(now()->addDays(170)));
        
        // Проверка аудита
        $this->assertEquals($correlationId, $order->correlation_id);
    }

    /**
     * Тест: Проверка B2B режима и срока гарантии.
     */
    public function test_b2b_repair_has_shorter_warranty(): void
    {
        $order = ApplianceRepairOrder::create([
            'tenant_id' => 1,
            'client_id' => 1,
            'appliance_type' => 'fridge',
            'is_b2b' => true,
            'address_json' => ['city' => 'Офис БЦ', 'house' => '10'],
        ]);

        $service = app(ApplianceRepairService::class);
        $service->completeRepair($order);

        // B2B Гарантия 90 дней
        $this->assertTrue($order->warranty_expires_at->diffInDays(now()) <= 90);
    }
}
