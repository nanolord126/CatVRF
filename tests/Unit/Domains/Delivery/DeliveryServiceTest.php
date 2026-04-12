<?php declare(strict_types=1);

namespace Tests\Unit\Domains\Delivery;

use PHPUnit\Framework\TestCase;

/**
 * Unit tests for DeliveryService.
 *
 * @covers \App\Domains\Delivery\Domain\Services\DeliveryService
 */
final class DeliveryServiceTest extends TestCase
{
    public function test_class_is_final(): void
    {
        $reflection = new \ReflectionClass(
            \App\Domains\Delivery\Domain\Services\DeliveryService::class
        );
        $this->assertTrue($reflection->isFinal(), 'DeliveryService must be final');
    }

    public function test_class_is_readonly(): void
    {
        $reflection = new \ReflectionClass(
            \App\Domains\Delivery\Domain\Services\DeliveryService::class
        );
        $this->assertTrue($reflection->isReadOnly(), 'DeliveryService must be readonly');
    }

    public function test_has_constructor_injection(): void
    {
        $reflection = new \ReflectionClass(
            \App\Domains\Delivery\Domain\Services\DeliveryService::class
        );
        $constructor = $reflection->getConstructor();
        $this->assertNotNull($constructor, 'DeliveryService must have __construct');
        $this->assertGreaterThan(0, $constructor->getNumberOfParameters());
    }

    public function test_create_method_exists(): void
    {
        $this->assertTrue(
            method_exists(\App\Domains\Delivery\Domain\Services\DeliveryService::class, 'create'),
            'DeliveryService must implement create()'
        );
    }

    public function test_update_method_exists(): void
    {
        $this->assertTrue(
            method_exists(\App\Domains\Delivery\Domain\Services\DeliveryService::class, 'update'),
            'DeliveryService must implement update()'
        );
    }

    public function test_delete_method_exists(): void
    {
        $this->assertTrue(
            method_exists(\App\Domains\Delivery\Domain\Services\DeliveryService::class, 'delete'),
            'DeliveryService must implement delete()'
        );
    }

    public function test_list_method_exists(): void
    {
        $this->assertTrue(
            method_exists(\App\Domains\Delivery\Domain\Services\DeliveryService::class, 'list'),
            'DeliveryService must implement list()'
        );
    }

    public function test_getById_method_exists(): void
    {
        $this->assertTrue(
            method_exists(\App\Domains\Delivery\Domain\Services\DeliveryService::class, 'getById'),
            'DeliveryService must implement getById()'
        );
    }

}
