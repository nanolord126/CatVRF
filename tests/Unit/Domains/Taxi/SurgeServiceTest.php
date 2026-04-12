<?php declare(strict_types=1);

namespace Tests\Unit\Domains\Taxi;

use PHPUnit\Framework\TestCase;

/**
 * Unit tests for SurgeService.
 *
 * @covers \App\Domains\Taxi\Domain\Services\SurgeService
 */
final class SurgeServiceTest extends TestCase
{
    public function test_class_is_final(): void
    {
        $reflection = new \ReflectionClass(
            \App\Domains\Taxi\Domain\Services\SurgeService::class
        );
        $this->assertTrue($reflection->isFinal(), 'SurgeService must be final');
    }

    public function test_class_is_readonly(): void
    {
        $reflection = new \ReflectionClass(
            \App\Domains\Taxi\Domain\Services\SurgeService::class
        );
        $this->assertTrue($reflection->isReadOnly(), 'SurgeService must be readonly');
    }

    public function test_has_constructor_injection(): void
    {
        $reflection = new \ReflectionClass(
            \App\Domains\Taxi\Domain\Services\SurgeService::class
        );
        $constructor = $reflection->getConstructor();
        $this->assertNotNull($constructor, 'SurgeService must have __construct');
        $this->assertGreaterThan(0, $constructor->getNumberOfParameters());
    }

    public function test_getMultiplierAtPoint_method_exists(): void
    {
        $this->assertTrue(
            method_exists(\App\Domains\Taxi\Domain\Services\SurgeService::class, 'getMultiplierAtPoint'),
            'SurgeService must implement getMultiplierAtPoint()'
        );
    }

    public function test_activateZone_method_exists(): void
    {
        $this->assertTrue(
            method_exists(\App\Domains\Taxi\Domain\Services\SurgeService::class, 'activateZone'),
            'SurgeService must implement activateZone()'
        );
    }

}
