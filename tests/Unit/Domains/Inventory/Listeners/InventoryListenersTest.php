<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\Inventory\Listeners;

use App\Domains\Inventory\Events\InventoryCheckCreated;
use App\Domains\Inventory\Events\InventoryCheckUpdated;
use App\Domains\Inventory\Events\StockReleased;
use App\Domains\Inventory\Events\StockReserved;
use App\Domains\Inventory\Events\StockUpdated;
use App\Domains\Inventory\Listeners\LogInventoryCheckCreated;
use App\Domains\Inventory\Listeners\LogInventoryCheckUpdated;
use App\Domains\Inventory\Listeners\LogStockReleased;
use App\Domains\Inventory\Listeners\LogStockReserved;
use App\Domains\Inventory\Listeners\LogStockUpdated;
use App\Services\AuditService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use ReflectionClass;

/**
 * Тесты Layer 7 — Listeners.
 *
 * Проверяем: final readonly, constructor DI (Logger + Audit),
 * handle() вызывает logger + audit, no facades.
 */
#[CoversClass(LogStockReserved::class)]
#[CoversClass(LogStockReleased::class)]
#[CoversClass(LogStockUpdated::class)]
#[CoversClass(LogInventoryCheckCreated::class)]
#[CoversClass(LogInventoryCheckUpdated::class)]
final class InventoryListenersTest extends TestCase
{
    /** @return list<array{class-string}> */
    public static function listenerClassesProvider(): array
    {
        return [
            [LogStockReserved::class],
            [LogStockReleased::class],
            [LogStockUpdated::class],
            [LogInventoryCheckCreated::class],
            [LogInventoryCheckUpdated::class],
        ];
    }

    /* ================================================================== */
    /*  Structural checks                                                  */
    /* ================================================================== */

    #[Test]
    #[DataProvider('listenerClassesProvider')]
    public function listener_is_final(string $class): void
    {
        self::assertTrue((new ReflectionClass($class))->isFinal(), "{$class} must be final");
    }

    #[Test]
    #[DataProvider('listenerClassesProvider')]
    public function listener_is_readonly(string $class): void
    {
        self::assertTrue((new ReflectionClass($class))->isReadOnly(), "{$class} must be readonly");
    }

    #[Test]
    #[DataProvider('listenerClassesProvider')]
    public function listener_has_strict_types(string $class): void
    {
        $ref  = new ReflectionClass($class);
        $code = (string) file_get_contents((string) $ref->getFileName());
        self::assertStringContainsString('declare(strict_types=1);', $code);
    }

    #[Test]
    #[DataProvider('listenerClassesProvider')]
    public function listener_has_no_facade_imports(string $class): void
    {
        $ref  = new ReflectionClass($class);
        $code = (string) file_get_contents((string) $ref->getFileName());
        self::assertStringNotContainsString('use Illuminate\Support\Facades\\', $code);
    }

    #[Test]
    #[DataProvider('listenerClassesProvider')]
    public function listener_constructor_requires_logger_and_audit(string $class): void
    {
        $ref  = new ReflectionClass($class);
        $ctor = $ref->getConstructor();
        self::assertNotNull($ctor, "{$class} must have a constructor");

        $params = $ctor->getParameters();
        $typeNames = array_map(
            static fn (\ReflectionParameter $p): string => (string) $p->getType(),
            $params,
        );

        self::assertContains(LoggerInterface::class, $typeNames, "{$class} constructor must require LoggerInterface");
        self::assertContains(AuditService::class, $typeNames, "{$class} constructor must require AuditService");
    }

    #[Test]
    #[DataProvider('listenerClassesProvider')]
    public function listener_has_handle_method(string $class): void
    {
        self::assertTrue(method_exists($class, 'handle'), "{$class} must have handle() method");
    }

    /* ================================================================== */
    /*  Behavioral: LogStockReserved                                       */
    /* ================================================================== */

    #[Test]
    public function log_stock_reserved_handle_calls_audit_record(): void
    {
        $this->assertListenerHandleCallsAuditRecord(LogStockReserved::class);
    }

    #[Test]
    public function log_stock_released_handle_calls_audit_record(): void
    {
        $this->assertListenerHandleCallsAuditRecord(LogStockReleased::class);
    }

    #[Test]
    public function log_stock_updated_handle_calls_audit_record(): void
    {
        $this->assertListenerHandleCallsAuditRecord(LogStockUpdated::class);
    }

    #[Test]
    public function log_inventory_check_created_handle_calls_audit_record(): void
    {
        $this->assertListenerHandleCallsAuditRecord(LogInventoryCheckCreated::class);
    }

    #[Test]
    public function log_inventory_check_updated_handle_calls_audit_record(): void
    {
        $this->assertListenerHandleCallsAuditRecord(LogInventoryCheckUpdated::class);
    }

    /* ================================================================== */
    /*  Source code asserts: logger->info + audit->record                   */
    /* ================================================================== */

    #[Test]
    #[DataProvider('listenerClassesProvider')]
    public function listener_handle_source_calls_logger_info(string $class): void
    {
        $ref  = new ReflectionClass($class);
        $code = (string) file_get_contents((string) $ref->getFileName());
        self::assertStringContainsString('$this->logger->info(', $code, "{$class}::handle() must call logger->info()");
    }

    #[Test]
    #[DataProvider('listenerClassesProvider')]
    public function listener_handle_source_calls_audit_record(string $class): void
    {
        $ref  = new ReflectionClass($class);
        $code = (string) file_get_contents((string) $ref->getFileName());
        self::assertStringContainsString('$this->audit->record(', $code, "{$class}::handle() must call audit->record()");
    }

    /* ================================================================== */
    /*  Helper: verify handle() body references audit->record              */
    /* ================================================================== */

    private function assertListenerHandleCallsAuditRecord(string $class): void
    {
        $ref  = new ReflectionClass($class);
        $code = (string) file_get_contents((string) $ref->getFileName());

        // Extract handle() body
        preg_match('/function handle\(.*?\{(.*)\}/s', $code, $matches);
        $handleBody = $matches[1] ?? '';

        self::assertStringContainsString(
            '$this->audit->record(',
            $handleBody,
            "{$class}::handle() must call \$this->audit->record()",
        );

        self::assertStringContainsString(
            '$this->logger->info(',
            $handleBody,
            "{$class}::handle() must call \$this->logger->info()",
        );
    }
}
