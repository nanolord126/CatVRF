<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\Inventory\Events;

use App\Domains\Inventory\Events\InventoryCheckCreated;
use App\Domains\Inventory\Events\InventoryCheckUpdated;
use App\Domains\Inventory\Events\StockReleased;
use App\Domains\Inventory\Events\StockReserved;
use App\Domains\Inventory\Events\StockUpdated;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

/**
 * Тесты Layer 6 — Events.
 *
 * Проверяем: final, public readonly props, broadcastPayload, strict_types.
 */
#[CoversClass(StockReserved::class)]
#[CoversClass(StockReleased::class)]
#[CoversClass(StockUpdated::class)]
#[CoversClass(InventoryCheckCreated::class)]
#[CoversClass(InventoryCheckUpdated::class)]
final class InventoryEventsTest extends TestCase
{
    /** @return list<array{class-string}> */
    public static function eventClassesProvider(): array
    {
        return [
            [StockReserved::class],
            [StockReleased::class],
            [StockUpdated::class],
            [InventoryCheckCreated::class],
            [InventoryCheckUpdated::class],
        ];
    }

    #[Test]
    #[DataProvider('eventClassesProvider')]
    public function event_is_final(string $class): void
    {
        self::assertTrue((new ReflectionClass($class))->isFinal(), "{$class} must be final");
    }

    #[Test]
    #[DataProvider('eventClassesProvider')]
    public function event_has_strict_types(string $class): void
    {
        $ref  = new ReflectionClass($class);
        $code = (string) file_get_contents((string) $ref->getFileName());
        self::assertStringContainsString('declare(strict_types=1);', $code);
    }

    #[Test]
    #[DataProvider('eventClassesProvider')]
    public function event_has_broadcast_payload_method(string $class): void
    {
        self::assertTrue(method_exists($class, 'broadcastPayload'), "{$class} must have broadcastPayload()");
    }

    #[Test]
    #[DataProvider('eventClassesProvider')]
    public function event_has_no_facade_imports(string $class): void
    {
        $ref  = new ReflectionClass($class);
        $code = (string) file_get_contents((string) $ref->getFileName());
        self::assertStringNotContainsString('use Illuminate\Support\Facades\\', $code);
    }

    /* ================================================================== */
    /*  StockReserved                                                      */
    /* ================================================================== */

    #[Test]
    public function stock_reserved_has_public_readonly_props(): void
    {
        $event = new StockReserved(
            productId: 1,
            warehouseId: 2,
            quantity: 5,
            reservationId: 10,
            tenantId: 100,
            correlationId: 'corr-1',
        );

        self::assertSame(1, $event->productId);
        self::assertSame(2, $event->warehouseId);
        self::assertSame(5, $event->quantity);
        self::assertSame(10, $event->reservationId);
        self::assertSame(100, $event->tenantId);
        self::assertSame('corr-1', $event->correlationId);
    }

    #[Test]
    public function stock_reserved_broadcast_payload_contains_all_fields(): void
    {
        $event = new StockReserved(1, 2, 5, 10, 100, 'corr-1');
        $payload = $event->broadcastPayload();

        self::assertArrayHasKey('product_id', $payload);
        self::assertArrayHasKey('warehouse_id', $payload);
        self::assertArrayHasKey('quantity', $payload);
        self::assertArrayHasKey('reservation_id', $payload);
        self::assertArrayHasKey('tenant_id', $payload);
        self::assertArrayHasKey('correlation_id', $payload);
    }

    /* ================================================================== */
    /*  StockReleased                                                      */
    /* ================================================================== */

    #[Test]
    public function stock_released_has_public_readonly_props(): void
    {
        $event = new StockReleased(1, 2, 5, 100, 'corr-2');

        self::assertSame(1, $event->productId);
        self::assertSame(2, $event->warehouseId);
        self::assertSame(5, $event->quantity);
        self::assertSame(100, $event->tenantId);
        self::assertSame('corr-2', $event->correlationId);
    }

    #[Test]
    public function stock_released_broadcast_payload_contains_all_fields(): void
    {
        $event = new StockReleased(1, 2, 5, 100, 'corr-2');
        $payload = $event->broadcastPayload();

        self::assertArrayHasKey('product_id', $payload);
        self::assertArrayHasKey('correlation_id', $payload);
        self::assertCount(5, $payload);
    }

    /* ================================================================== */
    /*  StockUpdated                                                       */
    /* ================================================================== */

    #[Test]
    public function stock_updated_has_public_readonly_props(): void
    {
        $event = new StockUpdated(1, 2, 100, 10, 90, 50, 'corr-3');

        self::assertSame(1, $event->productId);
        self::assertSame(2, $event->warehouseId);
        self::assertSame(100, $event->newQuantity);
        self::assertSame(10, $event->newReserved);
        self::assertSame(90, $event->available);
        self::assertSame(50, $event->tenantId);
        self::assertSame('corr-3', $event->correlationId);
    }

    #[Test]
    public function stock_updated_broadcast_payload_contains_all_fields(): void
    {
        $event = new StockUpdated(1, 2, 100, 10, 90, 50, 'corr-3');
        $payload = $event->broadcastPayload();

        self::assertArrayHasKey('quantity', $payload);
        self::assertArrayHasKey('reserved', $payload);
        self::assertArrayHasKey('available', $payload);
        self::assertCount(7, $payload);
    }

    /* ================================================================== */
    /*  InventoryCheckCreated                                              */
    /* ================================================================== */

    #[Test]
    public function inventory_check_created_has_public_readonly_props(): void
    {
        $event = new InventoryCheckCreated(1, 2, 100, 'corr-4');

        self::assertSame(1, $event->inventoryCheckId);
        self::assertSame(2, $event->warehouseId);
        self::assertSame(100, $event->tenantId);
        self::assertSame('corr-4', $event->correlationId);
    }

    #[Test]
    public function inventory_check_created_broadcast_payload(): void
    {
        $event = new InventoryCheckCreated(1, 2, 100, 'corr-4');
        $payload = $event->broadcastPayload();

        self::assertArrayHasKey('inventory_check_id', $payload);
        self::assertCount(4, $payload);
    }

    /* ================================================================== */
    /*  InventoryCheckUpdated                                              */
    /* ================================================================== */

    #[Test]
    public function inventory_check_updated_has_public_readonly_props(): void
    {
        $event = new InventoryCheckUpdated(1, 'planned', 'in_progress', 100, 'corr-5');

        self::assertSame(1, $event->inventoryCheckId);
        self::assertSame('planned', $event->oldStatus);
        self::assertSame('in_progress', $event->newStatus);
        self::assertSame(100, $event->tenantId);
        self::assertSame('corr-5', $event->correlationId);
    }

    #[Test]
    public function inventory_check_updated_broadcast_payload(): void
    {
        $event = new InventoryCheckUpdated(1, 'planned', 'in_progress', 100, 'corr-5');
        $payload = $event->broadcastPayload();

        self::assertArrayHasKey('old_status', $payload);
        self::assertArrayHasKey('new_status', $payload);
        self::assertCount(5, $payload);
    }
}
