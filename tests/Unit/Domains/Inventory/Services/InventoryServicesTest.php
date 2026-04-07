<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\Inventory\Services;

use App\Domains\Inventory\Services\AI\InventoryConstructorService;
use App\Domains\Inventory\Services\InventoryAuditService;
use App\Domains\Inventory\Services\InventoryService;
use App\Domains\Inventory\Services\WarehouseService;
use App\Services\AuditService;
use App\Services\FraudControlService;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Database\DatabaseManager;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use ReflectionClass;

/**
 * Тесты Layer 3 — Services.
 *
 * Проверяем: final readonly, constructor DI (NO facades),
 * обязательные зависимости (DatabaseManager, FraudControlService, AuditService),
 * публичный API, strict_types.
 */
#[CoversClass(InventoryService::class)]
#[CoversClass(WarehouseService::class)]
#[CoversClass(InventoryAuditService::class)]
#[CoversClass(InventoryConstructorService::class)]
final class InventoryServicesTest extends TestCase
{
    /** @return list<array{class-string}> */
    public static function serviceClassesProvider(): array
    {
        return [
            [InventoryService::class],
            [WarehouseService::class],
            [InventoryAuditService::class],
            [InventoryConstructorService::class],
        ];
    }

    /* ================================================================== */
    /*  Structural                                                         */
    /* ================================================================== */

    #[Test]
    #[DataProvider('serviceClassesProvider')]
    public function service_is_final(string $class): void
    {
        self::assertTrue((new ReflectionClass($class))->isFinal(), "{$class} must be final");
    }

    #[Test]
    #[DataProvider('serviceClassesProvider')]
    public function service_is_readonly(string $class): void
    {
        self::assertTrue((new ReflectionClass($class))->isReadOnly(), "{$class} must be readonly");
    }

    #[Test]
    #[DataProvider('serviceClassesProvider')]
    public function service_has_strict_types(string $class): void
    {
        $ref  = new ReflectionClass($class);
        $code = (string) file_get_contents((string) $ref->getFileName());
        self::assertStringContainsString('declare(strict_types=1);', $code);
    }

    #[Test]
    #[DataProvider('serviceClassesProvider')]
    public function service_has_no_facade_imports(string $class): void
    {
        $ref  = new ReflectionClass($class);
        $code = (string) file_get_contents((string) $ref->getFileName());
        self::assertStringNotContainsString('use Illuminate\Support\Facades\\', $code);
    }

    /* ================================================================== */
    /*  Constructor DI: mandatory dependencies                             */
    /* ================================================================== */

    #[Test]
    #[DataProvider('serviceClassesProvider')]
    public function service_requires_database_manager(string $class): void
    {
        $ctor = (new ReflectionClass($class))->getConstructor();
        self::assertNotNull($ctor);
        $types = array_map(fn (\ReflectionParameter $p) => (string) $p->getType(), $ctor->getParameters());
        self::assertContains(DatabaseManager::class, $types, "{$class} must inject DatabaseManager");
    }

    #[Test]
    #[DataProvider('serviceClassesProvider')]
    public function service_requires_fraud_control(string $class): void
    {
        $ctor = (new ReflectionClass($class))->getConstructor();
        $types = array_map(fn (\ReflectionParameter $p) => (string) $p->getType(), $ctor->getParameters());
        self::assertContains(FraudControlService::class, $types, "{$class} must inject FraudControlService");
    }

    #[Test]
    #[DataProvider('serviceClassesProvider')]
    public function service_requires_audit_service(string $class): void
    {
        $ctor = (new ReflectionClass($class))->getConstructor();
        $types = array_map(fn (\ReflectionParameter $p) => (string) $p->getType(), $ctor->getParameters());
        self::assertContains(AuditService::class, $types, "{$class} must inject AuditService");
    }

    #[Test]
    #[DataProvider('serviceClassesProvider')]
    public function service_requires_logger(string $class): void
    {
        $ctor = (new ReflectionClass($class))->getConstructor();
        $types = array_map(fn (\ReflectionParameter $p) => (string) $p->getType(), $ctor->getParameters());
        self::assertContains(LoggerInterface::class, $types, "{$class} must inject LoggerInterface");
    }

    /* ================================================================== */
    /*  InventoryService — public API                                      */
    /* ================================================================== */

    #[Test]
    public function inventory_service_has_reserve_method(): void
    {
        self::assertTrue(method_exists(InventoryService::class, 'reserve'));
    }

    #[Test]
    public function inventory_service_has_release_reservation_method(): void
    {
        self::assertTrue(method_exists(InventoryService::class, 'releaseReservation'));
    }

    #[Test]
    public function inventory_service_has_add_stock_method(): void
    {
        self::assertTrue(method_exists(InventoryService::class, 'addStock'));
    }

    #[Test]
    public function inventory_service_has_deduct_stock_method(): void
    {
        self::assertTrue(method_exists(InventoryService::class, 'deductStock'));
    }

    #[Test]
    public function inventory_service_has_adjust_method(): void
    {
        self::assertTrue(method_exists(InventoryService::class, 'adjust'));
    }

    #[Test]
    public function inventory_service_has_confirm_shipment_method(): void
    {
        self::assertTrue(method_exists(InventoryService::class, 'confirmShipment'));
    }

    #[Test]
    public function inventory_service_has_get_available_stock_method(): void
    {
        self::assertTrue(method_exists(InventoryService::class, 'getAvailableStock'));
    }

    #[Test]
    public function inventory_service_requires_dispatcher(): void
    {
        $ctor = (new ReflectionClass(InventoryService::class))->getConstructor();
        $types = array_map(fn (\ReflectionParameter $p) => (string) $p->getType(), $ctor->getParameters());
        self::assertContains(Dispatcher::class, $types);
    }

    /* ================================================================== */
    /*  WarehouseService — public API                                      */
    /* ================================================================== */

    #[Test]
    public function warehouse_service_has_create_method(): void
    {
        self::assertTrue(method_exists(WarehouseService::class, 'create'));
    }

    #[Test]
    public function warehouse_service_has_find_nearest_method(): void
    {
        self::assertTrue(method_exists(WarehouseService::class, 'findNearestWarehouse'));
    }

    #[Test]
    public function warehouse_service_has_list_for_tenant_method(): void
    {
        self::assertTrue(method_exists(WarehouseService::class, 'listForTenant'));
    }

    #[Test]
    public function warehouse_service_has_deactivate_method(): void
    {
        self::assertTrue(method_exists(WarehouseService::class, 'deactivate'));
    }

    /* ================================================================== */
    /*  InventoryAuditService — public API                                 */
    /* ================================================================== */

    #[Test]
    public function audit_service_has_start_audit_method(): void
    {
        self::assertTrue(method_exists(InventoryAuditService::class, 'startAudit'));
    }

    #[Test]
    public function audit_service_has_complete_audit_method(): void
    {
        self::assertTrue(method_exists(InventoryAuditService::class, 'completeAudit'));
    }

    #[Test]
    public function audit_service_requires_inventory_service(): void
    {
        $ctor = (new ReflectionClass(InventoryAuditService::class))->getConstructor();
        $types = array_map(fn (\ReflectionParameter $p) => (string) $p->getType(), $ctor->getParameters());
        self::assertContains(InventoryService::class, $types);
    }

    #[Test]
    public function audit_service_requires_dispatcher(): void
    {
        $ctor = (new ReflectionClass(InventoryAuditService::class))->getConstructor();
        $types = array_map(fn (\ReflectionParameter $p) => (string) $p->getType(), $ctor->getParameters());
        self::assertContains(Dispatcher::class, $types);
    }

    /* ================================================================== */
    /*  AI/InventoryConstructorService — public API                        */
    /* ================================================================== */

    #[Test]
    public function ai_constructor_has_analyze_and_recommend_method(): void
    {
        self::assertTrue(method_exists(InventoryConstructorService::class, 'analyzeDemandAndRecommend'));
    }

    #[Test]
    public function ai_constructor_requires_cache(): void
    {
        $ctor = (new ReflectionClass(InventoryConstructorService::class))->getConstructor();
        $types = array_map(fn (\ReflectionParameter $p) => (string) $p->getType(), $ctor->getParameters());
        self::assertContains(CacheRepository::class, $types);
    }

    /* ================================================================== */
    /*  No static calls in services (DB::, Cache::, Log:: etc.)            */
    /* ================================================================== */

    #[Test]
    #[DataProvider('serviceClassesProvider')]
    public function service_has_no_static_facade_calls(string $class): void
    {
        $ref  = new ReflectionClass($class);
        $code = (string) file_get_contents((string) $ref->getFileName());

        // Strip comments to avoid false positives on docblock mentions like "DB::transaction"
        $codeNoComments = preg_replace('#/\*.*?\*/#s', '', $code);
        $codeNoComments = preg_replace('#//.*$#m', '', (string) $codeNoComments);

        $facades = ['DB::', 'Cache::', 'Log::', 'Auth::', 'Redis::', 'Event::'];
        foreach ($facades as $facade) {
            self::assertStringNotContainsString($facade, (string) $codeNoComments, "{$class} must not use {$facade}");
        }
    }
}
