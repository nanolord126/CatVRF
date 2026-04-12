<?php declare(strict_types=1);

namespace Tests\Unit\Domains\Pet;

use PHPUnit\Framework\TestCase;

/**
 * Unit tests for BoardingService.
 *
 * @covers \App\Domains\Pet\Domain\Services\BoardingService
 */
final class BoardingServiceTest extends TestCase
{
    public function test_class_is_final(): void
    {
        $reflection = new \ReflectionClass(
            \App\Domains\Pet\Domain\Services\BoardingService::class
        );
        $this->assertTrue($reflection->isFinal(), 'BoardingService must be final');
    }

    public function test_class_is_readonly(): void
    {
        $reflection = new \ReflectionClass(
            \App\Domains\Pet\Domain\Services\BoardingService::class
        );
        $this->assertTrue($reflection->isReadOnly(), 'BoardingService must be readonly');
    }

    public function test_has_constructor_injection(): void
    {
        $reflection = new \ReflectionClass(
            \App\Domains\Pet\Domain\Services\BoardingService::class
        );
        $constructor = $reflection->getConstructor();
        $this->assertNotNull($constructor, 'BoardingService must have __construct');
        $this->assertGreaterThan(0, $constructor->getNumberOfParameters());
    }

    public function test_createReservation_method_exists(): void
    {
        $this->assertTrue(
            method_exists(\App\Domains\Pet\Domain\Services\BoardingService::class, 'createReservation'),
            'BoardingService must implement createReservation()'
        );
    }

    public function test_completeReservation_method_exists(): void
    {
        $this->assertTrue(
            method_exists(\App\Domains\Pet\Domain\Services\BoardingService::class, 'completeReservation'),
            'BoardingService must implement completeReservation()'
        );
    }

    public function test_cancelReservation_method_exists(): void
    {
        $this->assertTrue(
            method_exists(\App\Domains\Pet\Domain\Services\BoardingService::class, 'cancelReservation'),
            'BoardingService must implement cancelReservation()'
        );
    }

}
