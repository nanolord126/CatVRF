<?php declare(strict_types=1);

namespace Tests\Unit\Domains\Luxury;

use PHPUnit\Framework\TestCase;

/**
 * Unit tests for ConciergeService.
 *
 * @covers \App\Domains\Luxury\Domain\Services\ConciergeService
 */
final class ConciergeServiceTest extends TestCase
{
    public function test_class_is_final(): void
    {
        $reflection = new \ReflectionClass(
            \App\Domains\Luxury\Domain\Services\ConciergeService::class
        );
        $this->assertTrue($reflection->isFinal(), 'ConciergeService must be final');
    }

    public function test_class_is_readonly(): void
    {
        $reflection = new \ReflectionClass(
            \App\Domains\Luxury\Domain\Services\ConciergeService::class
        );
        $this->assertTrue($reflection->isReadOnly(), 'ConciergeService must be readonly');
    }

    public function test_has_constructor_injection(): void
    {
        $reflection = new \ReflectionClass(
            \App\Domains\Luxury\Domain\Services\ConciergeService::class
        );
        $constructor = $reflection->getConstructor();
        $this->assertNotNull($constructor, 'ConciergeService must have __construct');
        $this->assertGreaterThan(0, $constructor->getNumberOfParameters());
    }

    public function test_createBooking_method_exists(): void
    {
        $this->assertTrue(
            method_exists(\App\Domains\Luxury\Domain\Services\ConciergeService::class, 'createBooking'),
            'ConciergeService must implement createBooking()'
        );
    }

    public function test_getEligibleOffers_method_exists(): void
    {
        $this->assertTrue(
            method_exists(\App\Domains\Luxury\Domain\Services\ConciergeService::class, 'getEligibleOffers'),
            'ConciergeService must implement getEligibleOffers()'
        );
    }

}
