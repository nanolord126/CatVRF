<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\Inventory\Models;

use App\Domains\Inventory\Models\InventoryCheck;
use App\Domains\Inventory\Models\InventoryItem;
use App\Domains\Inventory\Models\Reservation;
use App\Domains\Inventory\Models\StockMovement;
use App\Domains\Inventory\Models\Warehouse;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionProperty;

/**
 * Тесты Layer 1 — Models.
 *
 * Проверяем: final, fillable, casts, table, tenant scope, UUID boot,
 * relationships, computed attributes.
 */
#[CoversClass(Warehouse::class)]
#[CoversClass(InventoryItem::class)]
#[CoversClass(StockMovement::class)]
#[CoversClass(Reservation::class)]
#[CoversClass(InventoryCheck::class)]
final class InventoryModelsTest extends TestCase
{
    /* ================================================================== */
    /*  Helpers                                                            */
    /* ================================================================== */

    private function makeModel(string $class): object
    {
        return (new ReflectionClass($class))->newInstanceWithoutConstructor();
    }

    /** @param array<string, mixed> $attributes */
    private function setAttributes(object $model, array $attributes): void
    {
        $prop = new ReflectionProperty(\Illuminate\Database\Eloquent\Model::class, 'attributes');
        $prop->setValue($model, $attributes);
    }

    /* ================================================================== */
    /*  1. All Models are final                                            */
    /* ================================================================== */

    /** @return list<array{class-string}> */
    public static function modelClassesProvider(): array
    {
        return [
            [Warehouse::class],
            [InventoryItem::class],
            [StockMovement::class],
            [Reservation::class],
            [InventoryCheck::class],
        ];
    }

    #[Test]
    #[DataProvider('modelClassesProvider')]
    public function model_is_final(string $class): void
    {
        $ref = new ReflectionClass($class);
        self::assertTrue($ref->isFinal(), "{$class} must be final");
    }

    /* ================================================================== */
    /*  2. Table names                                                     */
    /* ================================================================== */

    #[Test]
    public function warehouse_table_name(): void
    {
        $model = $this->makeModel(Warehouse::class);
        self::assertSame('warehouses', $model->getTable());
    }

    #[Test]
    public function inventory_item_table_name(): void
    {
        $model = $this->makeModel(InventoryItem::class);
        self::assertSame('inventories', $model->getTable());
    }

    #[Test]
    public function stock_movement_table_name(): void
    {
        $model = $this->makeModel(StockMovement::class);
        self::assertSame('stock_movements', $model->getTable());
    }

    #[Test]
    public function reservation_table_name(): void
    {
        $model = $this->makeModel(Reservation::class);
        self::assertSame('reservations', $model->getTable());
    }

    #[Test]
    public function inventory_check_table_name(): void
    {
        $model = $this->makeModel(InventoryCheck::class);
        self::assertSame('inventory_checks', $model->getTable());
    }

    /* ================================================================== */
    /*  3. Fillable arrays contain CANON mandatory fields                  */
    /* ================================================================== */

    #[Test]
    public function warehouse_fillable_contains_required_fields(): void
    {
        $model = $this->makeModel(Warehouse::class);
        $fillable = $model->getFillable();

        $required = ['tenant_id', 'business_group_id', 'uuid', 'name', 'address', 'lat', 'lon', 'is_active', 'correlation_id', 'tags'];

        foreach ($required as $field) {
            self::assertContains($field, $fillable, "Warehouse must have '{$field}' in fillable");
        }
    }

    #[Test]
    public function inventory_item_fillable_contains_required_fields(): void
    {
        $model = $this->makeModel(InventoryItem::class);
        $fillable = $model->getFillable();

        $required = ['warehouse_id', 'product_id', 'tenant_id', 'uuid', 'quantity', 'reserved', 'correlation_id', 'tags'];

        foreach ($required as $field) {
            self::assertContains($field, $fillable, "InventoryItem must have '{$field}' in fillable");
        }
    }

    #[Test]
    public function stock_movement_fillable_contains_required_fields(): void
    {
        $model = $this->makeModel(StockMovement::class);
        $fillable = $model->getFillable();

        $required = ['inventory_id', 'warehouse_id', 'tenant_id', 'uuid', 'type', 'quantity', 'source_type', 'correlation_id', 'tags'];

        foreach ($required as $field) {
            self::assertContains($field, $fillable, "StockMovement must have '{$field}' in fillable");
        }
    }

    #[Test]
    public function reservation_fillable_contains_required_fields(): void
    {
        $model = $this->makeModel(Reservation::class);
        $fillable = $model->getFillable();

        $required = ['inventory_id', 'tenant_id', 'uuid', 'quantity', 'expires_at', 'correlation_id', 'tags'];

        foreach ($required as $field) {
            self::assertContains($field, $fillable, "Reservation must have '{$field}' in fillable");
        }
    }

    #[Test]
    public function inventory_check_fillable_contains_required_fields(): void
    {
        $model = $this->makeModel(InventoryCheck::class);
        $fillable = $model->getFillable();

        $required = ['warehouse_id', 'tenant_id', 'employee_id', 'uuid', 'status', 'discrepancies', 'correlation_id', 'tags'];

        foreach ($required as $field) {
            self::assertContains($field, $fillable, "InventoryCheck must have '{$field}' in fillable");
        }
    }

    /* ================================================================== */
    /*  4. Casts                                                           */
    /* ================================================================== */

    #[Test]
    public function warehouse_casts_contain_json_and_boolean(): void
    {
        $model = $this->makeModel(Warehouse::class);
        $casts = $model->getCasts();

        self::assertSame('json', $casts['working_hours']);
        self::assertSame('json', $casts['tags']);
        self::assertSame('boolean', $casts['is_active']);
    }

    #[Test]
    public function inventory_item_casts_contain_integers_and_json(): void
    {
        $model = $this->makeModel(InventoryItem::class);
        $casts = $model->getCasts();

        self::assertSame('integer', $casts['quantity']);
        self::assertSame('integer', $casts['reserved']);
        self::assertSame('json', $casts['tags']);
    }

    #[Test]
    public function stock_movement_casts_contain_integer_and_json(): void
    {
        $model = $this->makeModel(StockMovement::class);
        $casts = $model->getCasts();

        self::assertSame('integer', $casts['quantity']);
        self::assertSame('json', $casts['tags']);
        self::assertSame('json', $casts['metadata']);
    }

    #[Test]
    public function reservation_casts_contain_integer_datetime_json(): void
    {
        $model = $this->makeModel(Reservation::class);
        $casts = $model->getCasts();

        self::assertSame('integer', $casts['quantity']);
        self::assertSame('datetime', $casts['expires_at']);
        self::assertSame('json', $casts['tags']);
    }

    #[Test]
    public function inventory_check_casts_contain_json(): void
    {
        $model = $this->makeModel(InventoryCheck::class);
        $casts = $model->getCasts();

        self::assertSame('json', $casts['discrepancies']);
        self::assertSame('json', $casts['tags']);
    }

    /* ================================================================== */
    /*  5. Relationships exist                                             */
    /* ================================================================== */

    #[Test]
    public function warehouse_has_inventory_items_relation(): void
    {
        self::assertTrue(method_exists(Warehouse::class, 'inventoryItems'));
    }

    #[Test]
    public function warehouse_has_stock_movements_relation(): void
    {
        self::assertTrue(method_exists(Warehouse::class, 'stockMovements'));
    }

    #[Test]
    public function warehouse_has_tenant_relation(): void
    {
        self::assertTrue(method_exists(Warehouse::class, 'tenant'));
    }

    #[Test]
    public function inventory_item_has_warehouse_relation(): void
    {
        self::assertTrue(method_exists(InventoryItem::class, 'warehouse'));
    }

    #[Test]
    public function inventory_item_has_stock_movements_relation(): void
    {
        self::assertTrue(method_exists(InventoryItem::class, 'stockMovements'));
    }

    #[Test]
    public function inventory_item_has_reservations_relation(): void
    {
        self::assertTrue(method_exists(InventoryItem::class, 'reservations'));
    }

    #[Test]
    public function inventory_item_has_tenant_relation(): void
    {
        self::assertTrue(method_exists(InventoryItem::class, 'tenant'));
    }

    #[Test]
    public function stock_movement_has_inventory_item_relation(): void
    {
        self::assertTrue(method_exists(StockMovement::class, 'inventoryItem'));
    }

    #[Test]
    public function stock_movement_has_warehouse_relation(): void
    {
        self::assertTrue(method_exists(StockMovement::class, 'warehouse'));
    }

    #[Test]
    public function reservation_has_inventory_item_relation(): void
    {
        self::assertTrue(method_exists(Reservation::class, 'inventoryItem'));
    }

    #[Test]
    public function inventory_check_has_warehouse_relation(): void
    {
        self::assertTrue(method_exists(InventoryCheck::class, 'warehouse'));
    }

    /* ================================================================== */
    /*  6. InventoryItem computed attribute                                */
    /* ================================================================== */

    #[Test]
    public function inventory_item_available_attribute_is_computed(): void
    {
        $model = $this->makeModel(InventoryItem::class);
        $this->setAttributes($model, ['quantity' => 100, 'reserved' => 30]);

        self::assertSame(70, $model->available);
    }

    #[Test]
    public function inventory_item_available_never_negative(): void
    {
        $model = $this->makeModel(InventoryItem::class);
        $this->setAttributes($model, ['quantity' => 5, 'reserved' => 10]);

        self::assertSame(0, $model->available);
    }

    #[Test]
    public function inventory_item_available_zero_when_fully_reserved(): void
    {
        $model = $this->makeModel(InventoryItem::class);
        $this->setAttributes($model, ['quantity' => 50, 'reserved' => 50]);

        self::assertSame(0, $model->available);
    }

    /* ================================================================== */
    /*  7. Reservation scope method exists                                 */
    /* ================================================================== */

    #[Test]
    public function reservation_has_scope_expired(): void
    {
        self::assertTrue(method_exists(Reservation::class, 'scopeExpired'));
    }

    /* ================================================================== */
    /*  8. No facades imported                                             */
    /* ================================================================== */

    #[Test]
    #[DataProvider('modelClassesProvider')]
    public function model_has_no_facade_imports(string $class): void
    {
        $ref  = new ReflectionClass($class);
        $file = (string) $ref->getFileName();
        $code = (string) file_get_contents($file);

        self::assertStringNotContainsString('use Illuminate\Support\Facades\\', $code, "{$class} must not import facades");
    }

    /* ================================================================== */
    /*  9. strict_types=1 declared                                         */
    /* ================================================================== */

    #[Test]
    #[DataProvider('modelClassesProvider')]
    public function model_has_strict_types(string $class): void
    {
        $ref  = new ReflectionClass($class);
        $file = (string) $ref->getFileName();
        $code = (string) file_get_contents($file);

        self::assertStringContainsString('declare(strict_types=1);', $code, "{$class} must declare strict_types=1");
    }
}
