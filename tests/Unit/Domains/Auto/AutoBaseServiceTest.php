<?php declare(strict_types=1);

namespace Tests\Unit\Domains\Auto;

use PHPUnit\Framework\TestCase;

/**
 * Unit tests for AutoBaseService.
 *
 * @covers \App\Domains\Auto\Domain\Services\AutoBaseService
 */
final class AutoBaseServiceTest extends TestCase
{
    public function test_class_is_final(): void
    {
        $reflection = new \ReflectionClass(
            \App\Domains\Auto\Domain\Services\AutoBaseService::class
        );
        $this->assertTrue($reflection->isFinal(), 'AutoBaseService must be final');
    }

    public function test_class_is_readonly(): void
    {
        $reflection = new \ReflectionClass(
            \App\Domains\Auto\Domain\Services\AutoBaseService::class
        );
        $this->assertTrue($reflection->isReadOnly(), 'AutoBaseService must be readonly');
    }

    public function test_has_constructor_injection(): void
    {
        $reflection = new \ReflectionClass(
            \App\Domains\Auto\Domain\Services\AutoBaseService::class
        );
        $constructor = $reflection->getConstructor();
        $this->assertNotNull($constructor, 'AutoBaseService must have __construct');
        $this->assertGreaterThan(0, $constructor->getNumberOfParameters());
    }

    public function test_getVerticalName_method_exists(): void
    {
        $this->assertTrue(
            method_exists(\App\Domains\Auto\Domain\Services\AutoBaseService::class, 'getVerticalName'),
            'AutoBaseService must implement getVerticalName()'
        );
    }

    public function test_getBaseCommissionRate_method_exists(): void
    {
        $this->assertTrue(
            method_exists(\App\Domains\Auto\Domain\Services\AutoBaseService::class, 'getBaseCommissionRate'),
            'AutoBaseService must implement getBaseCommissionRate()'
        );
    }

}
