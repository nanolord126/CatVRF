<?php declare(strict_types=1);

namespace Tests\Unit\Domains\FraudML;

use PHPUnit\Framework\TestCase;

/**
 * Unit tests for FraudMLCoordinatorService.
 *
 * @covers \App\Domains\FraudML\Domain\Services\FraudMLCoordinatorService
 */
final class FraudMLCoordinatorServiceTest extends TestCase
{
    public function test_class_is_final(): void
    {
        $reflection = new \ReflectionClass(
            \App\Domains\FraudML\Domain\Services\FraudMLCoordinatorService::class
        );
        $this->assertTrue($reflection->isFinal(), 'FraudMLCoordinatorService must be final');
    }

    public function test_class_is_readonly(): void
    {
        $reflection = new \ReflectionClass(
            \App\Domains\FraudML\Domain\Services\FraudMLCoordinatorService::class
        );
        $this->assertTrue($reflection->isReadOnly(), 'FraudMLCoordinatorService must be readonly');
    }

    public function test_has_constructor_injection(): void
    {
        $reflection = new \ReflectionClass(
            \App\Domains\FraudML\Domain\Services\FraudMLCoordinatorService::class
        );
        $constructor = $reflection->getConstructor();
        $this->assertNotNull($constructor, 'FraudMLCoordinatorService must have __construct');
        $this->assertGreaterThan(0, $constructor->getNumberOfParameters());
    }

    public function test_create_method_exists(): void
    {
        $this->assertTrue(
            method_exists(\App\Domains\FraudML\Domain\Services\FraudMLCoordinatorService::class, 'create'),
            'FraudMLCoordinatorService must implement create()'
        );
    }

    public function test_update_method_exists(): void
    {
        $this->assertTrue(
            method_exists(\App\Domains\FraudML\Domain\Services\FraudMLCoordinatorService::class, 'update'),
            'FraudMLCoordinatorService must implement update()'
        );
    }

    public function test_delete_method_exists(): void
    {
        $this->assertTrue(
            method_exists(\App\Domains\FraudML\Domain\Services\FraudMLCoordinatorService::class, 'delete'),
            'FraudMLCoordinatorService must implement delete()'
        );
    }

    public function test_list_method_exists(): void
    {
        $this->assertTrue(
            method_exists(\App\Domains\FraudML\Domain\Services\FraudMLCoordinatorService::class, 'list'),
            'FraudMLCoordinatorService must implement list()'
        );
    }

    public function test_getById_method_exists(): void
    {
        $this->assertTrue(
            method_exists(\App\Domains\FraudML\Domain\Services\FraudMLCoordinatorService::class, 'getById'),
            'FraudMLCoordinatorService must implement getById()'
        );
    }

}
