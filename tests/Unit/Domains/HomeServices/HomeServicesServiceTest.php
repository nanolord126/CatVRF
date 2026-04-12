<?php declare(strict_types=1);

namespace Tests\Unit\Domains\HomeServices;

use PHPUnit\Framework\TestCase;

/**
 * Unit tests for HomeServicesService.
 *
 * @covers \App\Domains\HomeServices\Domain\Services\HomeServicesService
 */
final class HomeServicesServiceTest extends TestCase
{
    public function test_class_is_final(): void
    {
        $reflection = new \ReflectionClass(
            \App\Domains\HomeServices\Domain\Services\HomeServicesService::class
        );
        $this->assertTrue($reflection->isFinal(), 'HomeServicesService must be final');
    }

    public function test_class_is_readonly(): void
    {
        $reflection = new \ReflectionClass(
            \App\Domains\HomeServices\Domain\Services\HomeServicesService::class
        );
        $this->assertTrue($reflection->isReadOnly(), 'HomeServicesService must be readonly');
    }

    public function test_has_constructor_injection(): void
    {
        $reflection = new \ReflectionClass(
            \App\Domains\HomeServices\Domain\Services\HomeServicesService::class
        );
        $constructor = $reflection->getConstructor();
        $this->assertNotNull($constructor, 'HomeServicesService must have __construct');
        $this->assertGreaterThan(0, $constructor->getNumberOfParameters());
    }

    public function test_bookService_method_exists(): void
    {
        $this->assertTrue(
            method_exists(\App\Domains\HomeServices\Domain\Services\HomeServicesService::class, 'bookService'),
            'HomeServicesService must implement bookService()'
        );
    }

    public function test_executeInTransaction_method_exists(): void
    {
        $this->assertTrue(
            method_exists(\App\Domains\HomeServices\Domain\Services\HomeServicesService::class, 'executeInTransaction'),
            'HomeServicesService must implement executeInTransaction()'
        );
    }

}
