<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\Inventory\Exceptions;

use App\Domains\Inventory\Exceptions\InsufficientStockException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

/**
 * Тесты исключения InsufficientStockException.
 *
 * Проверяем: final, extends RuntimeException, геттеры, context(), strict_types, no facades.
 */
#[CoversClass(InsufficientStockException::class)]
final class InventoryExceptionsTest extends TestCase
{
    /* ================================================================== */
    /*  Structural                                                         */
    /* ================================================================== */

    #[Test]
    public function exception_is_final(): void
    {
        self::assertTrue((new ReflectionClass(InsufficientStockException::class))->isFinal());
    }

    #[Test]
    public function exception_extends_runtime_exception(): void
    {
        $ref = new ReflectionClass(InsufficientStockException::class);
        self::assertTrue($ref->isSubclassOf(\RuntimeException::class));
    }

    #[Test]
    public function exception_has_strict_types(): void
    {
        $ref  = new ReflectionClass(InsufficientStockException::class);
        $code = (string) file_get_contents((string) $ref->getFileName());
        self::assertStringContainsString('declare(strict_types=1);', $code);
    }

    #[Test]
    public function exception_has_no_facades(): void
    {
        $ref  = new ReflectionClass(InsufficientStockException::class);
        $code = (string) file_get_contents((string) $ref->getFileName());
        self::assertStringNotContainsString('use Illuminate\Support\Facades\\', $code);
    }

    /* ================================================================== */
    /*  Constructor + Properties                                           */
    /* ================================================================== */

    #[Test]
    public function exception_has_private_readonly_properties(): void
    {
        $ref      = new ReflectionClass(InsufficientStockException::class);
        $expected = ['productId', 'warehouseId', 'requested', 'available', 'correlationId'];

        foreach ($expected as $propName) {
            self::assertTrue(
                $ref->hasProperty($propName),
                "InsufficientStockException must have property '{$propName}'",
            );

            $prop = $ref->getProperty($propName);
            self::assertTrue($prop->isPrivate(), "Property '{$propName}' must be private");
            self::assertTrue($prop->isReadOnly(), "Property '{$propName}' must be readonly");
        }
    }

    #[Test]
    public function exception_can_be_instantiated(): void
    {
        $ex = new InsufficientStockException(
            productId: 10,
            warehouseId: 3,
            requested: 50,
            available: 20,
            correlationId: 'corr-ins-001',
        );

        self::assertInstanceOf(\RuntimeException::class, $ex);
        self::assertInstanceOf(InsufficientStockException::class, $ex);
    }

    /* ================================================================== */
    /*  Getters                                                            */
    /* ================================================================== */

    #[Test]
    public function exception_getters_return_correct_values(): void
    {
        $ex = new InsufficientStockException(
            productId: 42,
            warehouseId: 7,
            requested: 100,
            available: 35,
            correlationId: 'corr-get-001',
        );

        self::assertSame(42, $ex->getProductId());
        self::assertSame(7, $ex->getWarehouseId());
        self::assertSame(100, $ex->getRequested());
        self::assertSame(35, $ex->getAvailable());
        self::assertSame('corr-get-001', $ex->getCorrelationId());
    }

    /* ================================================================== */
    /*  context()                                                          */
    /* ================================================================== */

    #[Test]
    public function exception_context_returns_array(): void
    {
        $ex = new InsufficientStockException(
            productId: 1,
            warehouseId: 2,
            requested: 3,
            available: 4,
            correlationId: 'corr-ctx-001',
        );

        $ctx = $ex->context();
        self::assertIsArray($ctx);
    }

    #[Test]
    public function exception_context_contains_required_keys(): void
    {
        $ex = new InsufficientStockException(
            productId: 1,
            warehouseId: 2,
            requested: 3,
            available: 4,
            correlationId: 'corr-ctx-002',
        );

        $ctx = $ex->context();

        self::assertArrayHasKey('product_id', $ctx);
        self::assertArrayHasKey('warehouse_id', $ctx);
        self::assertArrayHasKey('requested', $ctx);
        self::assertArrayHasKey('available', $ctx);
        self::assertArrayHasKey('correlation_id', $ctx);

        self::assertSame(1, $ctx['product_id']);
        self::assertSame(2, $ctx['warehouse_id']);
        self::assertSame(3, $ctx['requested']);
        self::assertSame(4, $ctx['available']);
        self::assertSame('corr-ctx-002', $ctx['correlation_id']);
    }

    /* ================================================================== */
    /*  message                                                            */
    /* ================================================================== */

    #[Test]
    public function exception_message_contains_product_info(): void
    {
        $ex = new InsufficientStockException(
            productId: 99,
            warehouseId: 5,
            requested: 80,
            available: 10,
            correlationId: 'corr-msg-001',
        );

        $message = $ex->getMessage();
        self::assertNotEmpty($message);
        self::assertStringContainsString('99', $message);
    }
}
