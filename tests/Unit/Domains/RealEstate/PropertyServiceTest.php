<?php declare(strict_types=1);

namespace Tests\Unit\Domains\RealEstate;

use PHPUnit\Framework\TestCase;

/**
 * Unit tests for PropertyService.
 *
 * @covers \App\Domains\RealEstate\Domain\Services\PropertyService
 */
final class PropertyServiceTest extends TestCase
{
    public function test_class_is_final(): void
    {
        $reflection = new \ReflectionClass(
            \App\Domains\RealEstate\Domain\Services\PropertyService::class
        );
        $this->assertTrue($reflection->isFinal(), 'PropertyService must be final');
    }

    public function test_class_is_readonly(): void
    {
        $reflection = new \ReflectionClass(
            \App\Domains\RealEstate\Domain\Services\PropertyService::class
        );
        $this->assertTrue($reflection->isReadOnly(), 'PropertyService must be readonly');
    }

    public function test_has_constructor_injection(): void
    {
        $reflection = new \ReflectionClass(
            \App\Domains\RealEstate\Domain\Services\PropertyService::class
        );
        $constructor = $reflection->getConstructor();
        $this->assertNotNull($constructor, 'PropertyService must have __construct');
        $this->assertGreaterThan(0, $constructor->getNumberOfParameters());
    }

    public function test_searchNearby_method_exists(): void
    {
        $this->assertTrue(
            method_exists(\App\Domains\RealEstate\Domain\Services\PropertyService::class, 'searchNearby'),
            'PropertyService must implement searchNearby()'
        );
    }

    public function test_toggleStatus_method_exists(): void
    {
        $this->assertTrue(
            method_exists(\App\Domains\RealEstate\Domain\Services\PropertyService::class, 'toggleStatus'),
            'PropertyService must implement toggleStatus()'
        );
    }

}
