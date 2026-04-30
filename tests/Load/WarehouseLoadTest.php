<?php

declare(strict_types=1);

namespace Tests\Load;

use Modules\Warehouse\Application\Services\WarehouseOrderTypeSwitchingService;
use Modules\Warehouse\Domain\ValueObjects\WarehouseId;
use Modules\Warehouse\Domain\Enums\OrderTypeEnum;
use Modules\Warehouse\Infrastructure\Models\InventoryItemModel;
use Modules\Warehouse\Infrastructure\Models\WarehouseModel;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

final class WarehouseLoadTest extends TestCase
{
    private WarehouseOrderTypeSwitchingService $service;
    private Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(WarehouseOrderTypeSwitchingService::class);
        $this->tenant = Tenant::factory()->create();
    }

    public function testWarehouseOrderTypeSwitching5000RPS(): void
    {
        $warehouse = WarehouseModel::factory()->create([
            'tenant_id' => $this->tenant->id,
        ]);

        $items = InventoryItemModel::factory()->count(1000)->create([
            'tenant_id' => $this->tenant->id,
            'warehouse_id' => $warehouse->id,
            'order_type' => 'b2b',
        ]);

        $startTime = microtime(true);
        $iterations = 5000;

        for ($i = 0; $i < $iterations; $i++) {
            $item = $items[$i % 1000];
            
            $this->service->switchOrderType(
                $item->id,
                $i % 2 === 0 ? OrderTypeEnum::B2C : OrderTypeEnum::B2B,
                userId: 1,
                tenantId: $this->tenant->id
            );
        }

        $endTime = microtime(true);
        $duration = $endTime - $startTime;
        $rps = $iterations / $duration;

        $this->assertGreaterThan(5000, $rps);
        $this->assertLessThan(1.0, $duration);
    }

    public function testWarehouseInventoryRetrieval10000RPS(): void
    {
        $warehouse = WarehouseModel::factory()->create([
            'tenant_id' => $this->tenant->id,
        ]);

        InventoryItemModel::factory()->count(5000)->create([
            'tenant_id' => $this->tenant->id,
            'warehouse_id' => $warehouse->id,
            'order_type' => 'b2b',
        ]);

        $startTime = microtime(true);
        $iterations = 10000;

        for ($i = 0; $i < $iterations; $i++) {
            $this->service->getInventoryByOrderType(
                WarehouseId::fromString($warehouse->id),
                OrderTypeEnum::B2B,
                $this->tenant->id
            );
        }

        $endTime = microtime(true);
        $duration = $endTime - $startTime;
        $rps = $iterations / $duration;

        $this->assertGreaterThan(10000, $rps);
    }

    public function testWarehouseColorMapping50000RPS(): void
    {
        $warehouse = WarehouseModel::factory()->create([
            'tenant_id' => $this->tenant->id,
        ]);

        InventoryItemModel::factory()->count(10000)->create([
            'tenant_id' => $this->tenant->id,
            'warehouse_id' => $warehouse->id,
            'order_type' => 'b2b',
        ]);

        $startTime = microtime(true);
        $iterations = 50000;

        for ($i = 0; $i < $iterations; $i++) {
            $this->service->getOrderColor(
                $i % 2 === 0 ? OrderTypeEnum::B2C : OrderTypeEnum::B2B
            );
        }

        $endTime = microtime(true);
        $duration = $endTime - $startTime;
        $rps = $iterations / $duration;

        $this->assertGreaterThan(50000, $rps);
    }

    public function testWarehouseBatchConversion5000RPS(): void
    {
        $warehouse = WarehouseModel::factory()->create([
            'tenant_id' => $this->tenant->id,
        ]);

        $items = InventoryItemModel::factory()->count(100)->create([
            'tenant_id' => $this->tenant->id,
            'warehouse_id' => $warehouse->id,
            'order_type' => 'b2b',
        ]);

        $startTime = microtime(true);
        $iterations = 5000;
        $itemIds = $items->pluck('id')->toArray();

        for ($i = 0; $i < $iterations; $i++) {
            $this->service->batchConvertOrderType(
                $itemIds,
                $i % 2 === 0 ? OrderTypeEnum::B2C : OrderTypeEnum::B2B,
                userId: 1,
                tenantId: $this->tenant->id
            );
        }

        $endTime = microtime(true);
        $duration = $endTime - $startTime;
        $rps = $iterations / $duration;

        $this->assertGreaterThan(5000, $rps);
    }

    public function testWarehouseSharedInventoryConversion5000RPS(): void
    {
        $warehouse = WarehouseModel::factory()->create([
            'tenant_id' => $this->tenant->id,
        ]);

        $items = InventoryItemModel::factory()->count(1000)->create([
            'tenant_id' => $this->tenant->id,
            'warehouse_id' => $warehouse->id,
            'order_type' => null,
            'quantity' => 1000,
        ]);

        $startTime = microtime(true);
        $iterations = 5000;

        for ($i = 0; $i < $iterations; $i++) {
            $item = $items[$i % 1000];
            
            $this->service->convertSharedToB2B(
                $item->id,
                10,
                userId: 1,
                tenantId: $this->tenant->id
            );
        }

        $endTime = microtime(true);
        $duration = $endTime - $startTime;
        $rps = $iterations / $duration;

        $this->assertGreaterThan(5000, $rps);
    }

    public function testWarehouseCacheInvalidationPerformance(): void
    {
        $warehouse = WarehouseModel::factory()->create([
            'tenant_id' => $this->tenant->id,
        ]);

        InventoryItemModel::factory()->count(1000)->create([
            'tenant_id' => $this->tenant->id,
            'warehouse_id' => $warehouse->id,
            'order_type' => 'b2b',
        ]);

        $startTime = microtime(true);
        $iterations = 10000;

        for ($i = 0; $i < $iterations; $i++) {
            $this->service->getInventoryByOrderType(
                WarehouseId::fromString($warehouse->id),
                OrderTypeEnum::B2B,
                $this->tenant->id
            );
        }

        $endTime = microtime(true);
        $duration = $endTime - $startTime;
        $rps = $iterations / $duration;

        $this->assertGreaterThan(10000, $rps);
    }

    public function testWarehouseColorDifferenceValidation50000RPS(): void
    {
        $startTime = microtime(true);
        $iterations = 50000;

        for ($i = 0; $i < $iterations; $i++) {
            $this->service->validateColorDifference();
        }

        $endTime = microtime(true);
        $duration = $endTime - $startTime;
        $rps = $iterations / $duration;

        $this->assertGreaterThan(50000, $rps);
    }

    public function testWarehouseConcurrentAccess(): void
    {
        $warehouse = WarehouseModel::factory()->create([
            'tenant_id' => $this->tenant->id,
        ]);

        $items = InventoryItemModel::factory()->count(100)->create([
            'tenant_id' => $this->tenant->id,
            'warehouse_id' => $warehouse->id,
            'order_type' => 'b2b',
        ]);

        $startTime = microtime(true);
        $concurrentRequests = 100;

        $processes = [];
        for ($i = 0; $i < $concurrentRequests; $i++) {
            $processes[] = $this->service->switchOrderType(
                $items[$i % 100]->id,
                OrderTypeEnum::B2C,
                userId: 1,
                tenantId: $this->tenant->id
            );
        }

        $endTime = microtime(true);
        $duration = $endTime - $startTime;

        $this->assertLessThan(0.5, $duration);
        $this->assertCount($concurrentRequests, $processes);
    }

    public function testWarehouseMemoryUsageUnderLoad(): void
    {
        $initialMemory = memory_get_usage(true);

        $warehouse = WarehouseModel::factory()->create([
            'tenant_id' => $this->tenant->id,
        ]);

        InventoryItemModel::factory()->count(10000)->create([
            'tenant_id' => $this->tenant->id,
            'warehouse_id' => $warehouse->id,
            'order_type' => 'b2b',
        ]);

        for ($i = 0; $i < 5000; $i++) {
            $this->service->getInventoryWithColorMapping(
                WarehouseId::fromString($warehouse->id)
            );
        }

        $finalMemory = memory_get_usage(true);
        $memoryIncrease = ($finalMemory - $initialMemory) / 1024 / 1024;

        $this->assertLessThan(100, $memoryIncrease);
    }

    public function testWarehouseDatabaseConnectionPooling(): void
    {
        $warehouse = WarehouseModel::factory()->create([
            'tenant_id' => $this->tenant->id,
        ]);

        InventoryItemModel::factory()->count(1000)->create([
            'tenant_id' => $this->tenant->id,
            'warehouse_id' => $warehouse->id,
            'order_type' => 'b2b',
        ]);

        $startTime = microtime(true);
        $iterations = 10000;

        for ($i = 0; $i < $iterations; $i++) {
            DB::table('warehouse_inventory_items')
                ->where('warehouse_id', $warehouse->id)
                ->where('order_type', 'b2b')
                ->get();
        }

        $endTime = microtime(true);
        $duration = $endTime - $startTime;
        $rps = $iterations / $duration;

        $this->assertGreaterThan(10000, $rps);
    }
}
