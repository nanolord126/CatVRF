<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\Inventory\Jobs;

use App\Domains\Inventory\Jobs\ProcessInventoryCheckJob;
use App\Domains\Inventory\Jobs\ReservationCleanupJob;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use ReflectionClass;

/**
 * Тесты Layer 8 — Jobs.
 *
 * Проверяем: final, ShouldQueue, конструктор НЕ содержит LoggerInterface,
 * handle() принимает зависимости через DI, strict_types, no facades.
 *
 * CANON: Job-классы не должны иметь LoggerInterface/AuditService в конструкторе
 * (сериализация сломает очередь). Только в handle().
 */
#[CoversClass(ReservationCleanupJob::class)]
#[CoversClass(ProcessInventoryCheckJob::class)]
final class InventoryJobsTest extends TestCase
{
    /** @return list<array{class-string}> */
    public static function jobClassesProvider(): array
    {
        return [
            [ReservationCleanupJob::class],
            [ProcessInventoryCheckJob::class],
        ];
    }

    /* ================================================================== */
    /*  Structural                                                         */
    /* ================================================================== */

    #[Test]
    #[DataProvider('jobClassesProvider')]
    public function job_is_final(string $class): void
    {
        self::assertTrue((new ReflectionClass($class))->isFinal(), "{$class} must be final");
    }

    #[Test]
    #[DataProvider('jobClassesProvider')]
    public function job_implements_should_queue(string $class): void
    {
        $ref = new ReflectionClass($class);
        self::assertTrue(
            $ref->implementsInterface(\Illuminate\Contracts\Queue\ShouldQueue::class),
            "{$class} must implement ShouldQueue",
        );
    }

    #[Test]
    #[DataProvider('jobClassesProvider')]
    public function job_has_strict_types(string $class): void
    {
        $ref  = new ReflectionClass($class);
        $code = (string) file_get_contents((string) $ref->getFileName());
        self::assertStringContainsString('declare(strict_types=1);', $code);
    }

    #[Test]
    #[DataProvider('jobClassesProvider')]
    public function job_has_no_facade_imports(string $class): void
    {
        $ref  = new ReflectionClass($class);
        $code = (string) file_get_contents((string) $ref->getFileName());
        self::assertStringNotContainsString('use Illuminate\Support\Facades\\', $code);
    }

    /* ================================================================== */
    /*  CRITICAL: constructor must NOT contain LoggerInterface              */
    /* ================================================================== */

    #[Test]
    #[DataProvider('jobClassesProvider')]
    public function job_constructor_does_not_contain_logger(string $class): void
    {
        $ref  = new ReflectionClass($class);
        $ctor = $ref->getConstructor();

        if ($ctor === null) {
            self::assertTrue(true); // no constructor is fine
            return;
        }

        $types = array_map(
            fn (\ReflectionParameter $p) => (string) $p->getType(),
            $ctor->getParameters(),
        );

        self::assertNotContains(
            LoggerInterface::class,
            $types,
            "{$class} constructor MUST NOT contain LoggerInterface (breaks serialization for queue)",
        );
    }

    #[Test]
    #[DataProvider('jobClassesProvider')]
    public function job_constructor_does_not_contain_audit_service(string $class): void
    {
        $ref  = new ReflectionClass($class);
        $ctor = $ref->getConstructor();

        if ($ctor === null) {
            self::assertTrue(true);
            return;
        }

        $types = array_map(
            fn (\ReflectionParameter $p) => (string) $p->getType(),
            $ctor->getParameters(),
        );

        self::assertNotContains(
            \App\Services\AuditService::class,
            $types,
            "{$class} constructor MUST NOT contain AuditService (breaks serialization for queue)",
        );
    }

    /* ================================================================== */
    /*  handle() must accept services as DI parameters                     */
    /* ================================================================== */

    #[Test]
    #[DataProvider('jobClassesProvider')]
    public function job_handle_accepts_logger_via_di(string $class): void
    {
        $ref    = new ReflectionClass($class);
        $handle = $ref->getMethod('handle');

        $types = array_map(
            fn (\ReflectionParameter $p) => (string) $p->getType(),
            $handle->getParameters(),
        );

        self::assertContains(
            LoggerInterface::class,
            $types,
            "{$class}::handle() must accept LoggerInterface via DI",
        );
    }

    /* ================================================================== */
    /*  ReservationCleanupJob specifics                                    */
    /* ================================================================== */

    #[Test]
    public function reservation_cleanup_job_has_tries(): void
    {
        $ref = new ReflectionClass(ReservationCleanupJob::class);
        self::assertTrue($ref->hasProperty('tries'));

        $job = $ref->newInstanceWithoutConstructor();
        $prop = $ref->getProperty('tries');
        self::assertGreaterThanOrEqual(1, $prop->getValue($job));
    }

    /* ================================================================== */
    /*  ProcessInventoryCheckJob specifics                                 */
    /* ================================================================== */

    #[Test]
    public function process_inventory_check_job_constructor_accepts_only_primitives(): void
    {
        $ref  = new ReflectionClass(ProcessInventoryCheckJob::class);
        $ctor = $ref->getConstructor();
        self::assertNotNull($ctor);

        foreach ($ctor->getParameters() as $param) {
            $type = (string) $param->getType();
            self::assertContains(
                $type,
                ['int', 'string', 'float', 'bool', 'array'],
                "ProcessInventoryCheckJob constructor param '{$param->getName()}' must be a primitive type, got '{$type}'",
            );
        }
    }

    #[Test]
    public function process_inventory_check_job_has_getters(): void
    {
        self::assertTrue(method_exists(ProcessInventoryCheckJob::class, 'getCheckId'));
        self::assertTrue(method_exists(ProcessInventoryCheckJob::class, 'getCorrelationId'));
    }

    #[Test]
    public function process_inventory_check_job_has_failed_method(): void
    {
        self::assertTrue(method_exists(ProcessInventoryCheckJob::class, 'failed'));
    }

    #[Test]
    public function process_inventory_check_job_has_timeout(): void
    {
        $ref = new ReflectionClass(ProcessInventoryCheckJob::class);
        self::assertTrue($ref->hasProperty('timeout'));

        $job = $ref->newInstanceWithoutConstructor();
        $prop = $ref->getProperty('timeout');
        self::assertGreaterThan(0, $prop->getValue($job));
    }
}
