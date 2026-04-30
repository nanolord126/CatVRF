<?php

declare(strict_types=1);

namespace Tests\Fraud;

use Modules\Warehouse\Application\Services\WarehouseOrderTypeSwitchingService;
use Modules\Warehouse\Domain\ValueObjects\WarehouseId;
use Modules\Warehouse\Domain\Enums\OrderTypeEnum;
use Modules\Warehouse\Infrastructure\Models\InventoryItemModel;
use Modules\Warehouse\Infrastructure\Models\WarehouseModel;
use Modules\FraudDetection\Interfaces\Services\HttpFraudScoringService;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class WarehouseFraudDetectionTest extends TestCase
{
    use RefreshDatabase;

    private WarehouseOrderTypeSwitchingService $warehouseService;
    private HttpFraudScoringService $fraudService;
    private Tenant $tenant;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->warehouseService = app(WarehouseOrderTypeSwitchingService::class);
        $this->fraudService = app(HttpFraudScoringService::class);
        $this->tenant = Tenant::factory()->create();
        $this->user = User::factory()->create(['tenant_id' => $this->tenant->id]);
    }

    public function testDetectSuspiciousOrderTypeSwitching(): void
    {
        $warehouse = WarehouseModel::factory()->create([
            'tenant_id' => $this->tenant->id,
        ]);

        $inventoryItem = InventoryItemModel::factory()->create([
            'tenant_id' => $this->tenant->id,
            'warehouse_id' => $warehouse->id,
            'product_sku' => 'SKU-001',
            'product_name' => 'High Value Product',
            'quantity' => 1000,
            'order_type' => 'b2b',
        ]);

        $fraudScore = $this->fraudService->scoreTransaction([
            'entity_type' => 'InventoryItem',
            'entity_id' => $inventoryItem->id,
            'action' => 'order_type_switch',
            'user_id' => $this->user->id,
            'tenant_id' => $this->tenant->id,
            'metadata' => [
                'from_type' => 'b2b',
                'to_type' => 'b2c',
                'quantity' => 1000,
            ],
        ]);

        $this->assertArrayHasKey('score', $fraudScore);
        $this->assertArrayHasKey('risk_level', $fraudScore);
        $this->assertIsFloat($fraudScore['score']);
    }

    public function testDetectRapidConversions(): void
    {
        $warehouse = WarehouseModel::factory()->create([
            'tenant_id' => $this->tenant->id,
        ]);

        $inventoryItem = InventoryItemModel::factory()->create([
            'tenant_id' => $this->tenant->id,
            'warehouse_id' => $warehouse->id,
            'order_type' => 'b2b',
        ]);

        for ($i = 0; $i < 10; $i++) {
            $this->warehouseService->switchOrderType(
                $inventoryItem->id,
                $i % 2 === 0 ? OrderTypeEnum::B2C : OrderTypeEnum::B2B,
                $this->user->id,
                $this->tenant->id
            );
        }

        $fraudScore = $this->fraudService->scoreTransaction([
            'entity_type' => 'InventoryItem',
            'entity_id' => $inventoryItem->id,
            'action' => 'rapid_conversion',
            'user_id' => $this->user->id,
            'tenant_id' => $this->tenant->id,
            'metadata' => [
                'conversion_count' => 10,
                'time_period_minutes' => 1,
            ],
        ]);

        $this->assertGreaterThan(70, $fraudScore['score']);
        $this->assertEquals('high', $fraudScore['risk_level']);
    }

    public function testDetectLargeQuantityConversion(): void
    {
        $warehouse = WarehouseModel::factory()->create([
            'tenant_id' => $this->tenant->id,
            'capacity' => 10000,
        ]);

        $inventoryItem = InventoryItemModel::factory()->create([
            'tenant_id' => $this->tenant->id,
            'warehouse_id' => $warehouse->id,
            'quantity' => 9000,
            'order_type' => 'b2b',
        ]);

        $fraudScore = $this->fraudService->scoreTransaction([
            'entity_type' => 'InventoryItem',
            'entity_id' => $inventoryItem->id,
            'action' => 'large_quantity_conversion',
            'user_id' => $this->user->id,
            'tenant_id' => $this->tenant->id,
            'metadata' => [
                'quantity' => 9000,
                'capacity_percentage' => 90,
            ],
        ]);

        $this->assertGreaterThan(50, $fraudScore['score']);
    }

    public function testDetectUnauthorizedWarehouseAccess(): void
    {
        $otherUser = User::factory()->create();

        $warehouse = WarehouseModel::factory()->create([
            'tenant_id' => $this->tenant->id,
        ]);

        $fraudScore = $this->fraudService->scoreTransaction([
            'entity_type' => 'Warehouse',
            'entity_id' => $warehouse->id,
            'action' => 'unauthorized_access_attempt',
            'user_id' => $otherUser->id,
            'tenant_id' => $this->tenant->id,
            'metadata' => [
                'attempted_tenant_id' => $this->tenant->id,
                'user_tenant_id' => $otherUser->tenant_id,
            ],
        ]);

        $this->assertEquals('critical', $fraudScore['risk_level']);
        $this->assertGreaterThan(90, $fraudScore['score']);
    }

    public function testDetectInventoryManipulation(): void
    {
        $warehouse = WarehouseModel::factory()->create([
            'tenant_id' => $this->tenant->id,
        ]);

        $inventoryItem = InventoryItemModel::factory()->create([
            'tenant_id' => $this->tenant->id,
            'warehouse_id' => $warehouse->id,
            'quantity' => 100,
            'reserved_quantity' => 0,
        ]);

        $fraudScore = $this->fraudService->scoreTransaction([
            'entity_type' => 'InventoryItem',
            'entity_id' => $inventoryItem->id,
            'action' => 'suspicious_quantity_change',
            'user_id' => $this->user->id,
            'tenant_id' => $this->tenant->id,
            'metadata' => [
                'old_quantity' => 100,
                'new_quantity' => 10000,
                'change_percentage' => 9900,
            ],
        ]);

        $this->assertGreaterThan(80, $fraudScore['score']);
    }

    public function testDetectCrossTenantDataAccess(): void
    {
        $otherTenant = Tenant::factory()->create();

        $fraudScore = $this->fraudService->scoreTransaction([
            'entity_type' => 'Warehouse',
            'action' => 'cross_tenant_access',
            'user_id' => $this->user->id,
            'tenant_id' => $this->tenant->id,
            'metadata' => [
                'target_tenant_id' => $otherTenant->id,
            ],
        ]);

        $this->assertEquals('critical', $fraudScore['risk_level']);
    }

    public function testFraudCheckBlocksSuspiciousAction(): void
    {
        $warehouse = WarehouseModel::factory()->create([
            'tenant_id' => $this->tenant->id,
        ]);

        $inventoryItem = InventoryItemModel::factory()->create([
            'tenant_id' => $this->tenant->id,
            'warehouse_id' => $warehouse->id,
            'quantity' => 100,
            'order_type' => 'b2b',
        ]);

        $fraudScore = $this->fraudService->scoreTransaction([
            'entity_type' => 'InventoryItem',
            'entity_id' => $inventoryItem->id,
            'action' => 'order_type_switch',
            'user_id' => $this->user->id,
            'tenant_id' => $this->tenant->id,
            'metadata' => [
                'from_type' => 'b2b',
                'to_type' => 'b2c',
            ],
        ]);

        if ($fraudScore['risk_level'] === 'critical') {
            $this->expectException(\RuntimeException::class);
            $this->expectExceptionMessage('Transaction blocked by fraud detection');
        }

        $this->warehouseService->switchOrderType(
            $inventoryItem->id,
            OrderTypeEnum::B2C,
            $this->user->id,
            $this->tenant->id
        );
    }
}
