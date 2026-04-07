<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\Inventory\DTOs;

use App\Domains\Inventory\DTOs\CreateAdjustmentDto;
use App\Domains\Inventory\DTOs\CreateReservationDto;
use App\Domains\Inventory\DTOs\CreateStockMovementDto;
use App\Domains\Inventory\DTOs\ImportResultDto;
use App\Domains\Inventory\DTOs\SearchInventoryDto;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

/**
 * Тесты Layer 2 — DTOs.
 *
 * Проверяем: final readonly, public readonly props, fromArray, toArray, construction.
 */
#[CoversClass(CreateReservationDto::class)]
#[CoversClass(CreateStockMovementDto::class)]
#[CoversClass(CreateAdjustmentDto::class)]
#[CoversClass(SearchInventoryDto::class)]
#[CoversClass(ImportResultDto::class)]
final class InventoryDtosTest extends TestCase
{
    /* ================================================================== */
    /*  Helpers                                                            */
    /* ================================================================== */

    /** @return list<array{class-string}> */
    public static function dtoClassesProvider(): array
    {
        return [
            [CreateReservationDto::class],
            [CreateStockMovementDto::class],
            [CreateAdjustmentDto::class],
            [SearchInventoryDto::class],
            [ImportResultDto::class],
        ];
    }

    /* ================================================================== */
    /*  1. Structural: final readonly                                      */
    /* ================================================================== */

    #[Test]
    #[DataProvider('dtoClassesProvider')]
    public function dto_is_final(string $class): void
    {
        $ref = new ReflectionClass($class);
        self::assertTrue($ref->isFinal(), "{$class} must be final");
    }

    #[Test]
    #[DataProvider('dtoClassesProvider')]
    public function dto_is_readonly(string $class): void
    {
        $ref = new ReflectionClass($class);
        self::assertTrue($ref->isReadOnly(), "{$class} must be readonly");
    }

    #[Test]
    #[DataProvider('dtoClassesProvider')]
    public function dto_has_strict_types(string $class): void
    {
        $ref  = new ReflectionClass($class);
        $file = (string) $ref->getFileName();
        $code = (string) file_get_contents($file);

        self::assertStringContainsString('declare(strict_types=1);', $code);
    }

    /* ================================================================== */
    /*  2. CreateReservationDto                                            */
    /* ================================================================== */

    #[Test]
    public function create_reservation_dto_construction(): void
    {
        $dto = new CreateReservationDto(
            tenantId: 1,
            productId: 42,
            warehouseId: 3,
            quantity: 5,
            sourceType: 'cart',
            sourceId: 100,
            correlationId: 'corr-abc-123',
        );

        self::assertSame(1, $dto->tenantId);
        self::assertSame(42, $dto->productId);
        self::assertSame(3, $dto->warehouseId);
        self::assertSame(5, $dto->quantity);
        self::assertSame('cart', $dto->sourceType);
        self::assertSame(100, $dto->sourceId);
        self::assertSame('corr-abc-123', $dto->correlationId);
        self::assertNull($dto->businessGroupId);
        self::assertNull($dto->cartId);
        self::assertNull($dto->orderId);
        self::assertNull($dto->expiresAt);
    }

    #[Test]
    public function create_reservation_dto_from_array(): void
    {
        $dto = CreateReservationDto::fromArray([
            'tenant_id'      => 2,
            'product_id'     => 10,
            'warehouse_id'   => 5,
            'quantity'        => 3,
            'source_type'    => 'order',
            'source_id'      => 50,
            'correlation_id' => 'corr-xyz',
            'cart_id'        => 99,
        ]);

        self::assertSame(2, $dto->tenantId);
        self::assertSame(10, $dto->productId);
        self::assertSame(3, $dto->quantity);
        self::assertSame(99, $dto->cartId);
    }

    #[Test]
    public function create_reservation_dto_to_array(): void
    {
        $dto = new CreateReservationDto(
            tenantId: 1,
            productId: 42,
            warehouseId: 3,
            quantity: 5,
            sourceType: 'cart',
            sourceId: 100,
            correlationId: 'corr-abc',
        );

        $arr = $dto->toArray();

        self::assertSame(1, $arr['tenant_id']);
        self::assertSame(42, $arr['product_id']);
        self::assertSame(3, $arr['warehouse_id']);
        self::assertSame(5, $arr['quantity']);
        self::assertSame('cart', $arr['source_type']);
        self::assertSame(100, $arr['source_id']);
        self::assertSame('corr-abc', $arr['correlation_id']);
    }

    /* ================================================================== */
    /*  3. CreateStockMovementDto                                          */
    /* ================================================================== */

    #[Test]
    public function create_stock_movement_dto_construction(): void
    {
        $dto = new CreateStockMovementDto(
            tenantId: 1,
            inventoryId: 5,
            warehouseId: 3,
            type: 'in',
            quantity: 100,
            sourceType: 'supplier',
            correlationId: 'corr-001',
        );

        self::assertSame(1, $dto->tenantId);
        self::assertSame(5, $dto->inventoryId);
        self::assertSame(3, $dto->warehouseId);
        self::assertSame('in', $dto->type);
        self::assertSame(100, $dto->quantity);
        self::assertSame('supplier', $dto->sourceType);
        self::assertNull($dto->sourceId);
        self::assertNull($dto->metadata);
    }

    #[Test]
    public function create_stock_movement_dto_from_array(): void
    {
        $dto = CreateStockMovementDto::fromArray([
            'tenant_id'      => 3,
            'inventory_id'   => 7,
            'warehouse_id'   => 4,
            'type'           => 'out',
            'quantity'        => 20,
            'source_type'    => 'order',
            'correlation_id' => 'corr-002',
            'source_id'      => 55,
            'metadata'       => ['note' => 'test'],
        ]);

        self::assertSame(3, $dto->tenantId);
        self::assertSame(55, $dto->sourceId);
        self::assertSame(['note' => 'test'], $dto->metadata);
    }

    #[Test]
    public function create_stock_movement_dto_to_array(): void
    {
        $dto = new CreateStockMovementDto(
            tenantId: 1,
            inventoryId: 5,
            warehouseId: 3,
            type: 'in',
            quantity: 100,
            sourceType: 'supplier',
            correlationId: 'corr-001',
        );

        $arr = $dto->toArray();
        self::assertSame(1, $arr['tenant_id']);
        self::assertSame(5, $arr['inventory_id']);
        self::assertSame('in', $arr['type']);
    }

    /* ================================================================== */
    /*  4. CreateAdjustmentDto                                             */
    /* ================================================================== */

    #[Test]
    public function create_adjustment_dto_construction(): void
    {
        $dto = new CreateAdjustmentDto(
            tenantId: 1,
            productId: 42,
            warehouseId: 3,
            newQuantity: 75,
            reason: 'Inventory audit correction',
            correlationId: 'corr-adj-001',
        );

        self::assertSame(1, $dto->tenantId);
        self::assertSame(42, $dto->productId);
        self::assertSame(75, $dto->newQuantity);
        self::assertSame('Inventory audit correction', $dto->reason);
        self::assertNull($dto->businessGroupId);
        self::assertNull($dto->employeeId);
    }

    #[Test]
    public function create_adjustment_dto_from_array(): void
    {
        $dto = CreateAdjustmentDto::fromArray([
            'tenant_id'      => 1,
            'product_id'     => 42,
            'warehouse_id'   => 3,
            'new_quantity'   => 75,
            'reason'         => 'test',
            'correlation_id' => 'corr-adj-002',
            'employee_id'    => 10,
        ]);

        self::assertSame(75, $dto->newQuantity);
        self::assertSame(10, $dto->employeeId);
    }

    #[Test]
    public function create_adjustment_dto_to_array(): void
    {
        $dto = new CreateAdjustmentDto(
            tenantId: 1,
            productId: 42,
            warehouseId: 3,
            newQuantity: 75,
            reason: 'test',
            correlationId: 'corr-adj-003',
        );

        $arr = $dto->toArray();
        self::assertSame(75, $arr['new_quantity']);
        self::assertSame('test', $arr['reason']);
    }

    /* ================================================================== */
    /*  5. SearchInventoryDto                                              */
    /* ================================================================== */

    #[Test]
    public function search_inventory_dto_defaults(): void
    {
        $dto = new SearchInventoryDto(tenantId: 1);

        self::assertSame(1, $dto->tenantId);
        self::assertNull($dto->warehouseId);
        self::assertNull($dto->productId);
        self::assertNull($dto->inStockOnly);
        self::assertSame('created_at', $dto->sortBy);
        self::assertSame('desc', $dto->sortDirection);
        self::assertSame(20, $dto->perPage);
        self::assertSame(1, $dto->page);
    }

    #[Test]
    public function search_inventory_dto_from_array(): void
    {
        $dto = SearchInventoryDto::fromArray([
            'tenant_id'     => 5,
            'warehouse_id'  => 2,
            'in_stock_only' => true,
            'per_page'      => 50,
        ]);

        self::assertSame(5, $dto->tenantId);
        self::assertSame(2, $dto->warehouseId);
        self::assertTrue($dto->inStockOnly);
        self::assertSame(50, $dto->perPage);
    }

    #[Test]
    public function search_inventory_dto_to_array(): void
    {
        $dto = new SearchInventoryDto(tenantId: 1, warehouseId: 2);
        $arr = $dto->toArray();

        self::assertArrayHasKey('tenant_id', $arr);
        self::assertArrayHasKey('warehouse_id', $arr);
        self::assertSame(2, $arr['warehouse_id']);
    }

    /* ================================================================== */
    /*  6. ImportResultDto                                                 */
    /* ================================================================== */

    #[Test]
    public function import_result_dto_construction(): void
    {
        $dto = new ImportResultDto(
            totalRows: 100,
            imported: 90,
            skipped: 5,
            errors: ['Row 3: invalid SKU'],
            correlationId: 'corr-imp-001',
        );

        self::assertSame(100, $dto->totalRows);
        self::assertSame(90, $dto->imported);
        self::assertSame(5, $dto->skipped);
        self::assertCount(1, $dto->errors);
        self::assertSame('corr-imp-001', $dto->correlationId);
    }

    #[Test]
    public function import_result_has_errors_returns_true_when_errors_present(): void
    {
        $dto = new ImportResultDto(10, 8, 1, ['error1'], 'corr');
        self::assertTrue($dto->hasErrors());
    }

    #[Test]
    public function import_result_has_errors_returns_false_when_empty(): void
    {
        $dto = new ImportResultDto(10, 10, 0, [], 'corr');
        self::assertFalse($dto->hasErrors());
    }

    #[Test]
    public function import_result_failed_count(): void
    {
        $dto = new ImportResultDto(100, 90, 5, ['e1', 'e2', 'e3', 'e4', 'e5'], 'corr');
        self::assertSame(5, $dto->failedCount());
    }

    #[Test]
    public function import_result_to_array(): void
    {
        $dto = new ImportResultDto(50, 45, 3, ['err'], 'corr');
        $arr = $dto->toArray();

        self::assertSame(50, $arr['total_rows']);
        self::assertSame(45, $arr['imported']);
        self::assertSame(3, $arr['skipped']);
        self::assertSame(2, $arr['failed']);
        self::assertSame(['err'], $arr['errors']);
        self::assertSame('corr', $arr['correlation_id']);
    }
}
