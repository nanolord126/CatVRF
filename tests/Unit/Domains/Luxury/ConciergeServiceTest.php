<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\Luxury;

use PHPUnit\Framework\TestCase;
use App\Domains\Luxury\Domain\Services\ConciergeService;

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
            ConciergeService::class
        );
        $this->assertTrue($reflection->isFinal(), 'ConciergeService must be final');
    }

    public function test_class_is_readonly(): void
    {
        $reflection = new \ReflectionClass(
            ConciergeService::class
        );
        $this->assertTrue($reflection->isReadOnly(), 'ConciergeService must be readonly');
    }

    public function test_has_constructor_injection(): void
    {
        $reflection = new \ReflectionClass(
            ConciergeService::class
        );
        $constructor = $reflection->getConstructor();
        $this->assertNotNull($constructor, 'ConciergeService must have __construct');
        $this->assertGreaterThan(0, $constructor->getNumberOfParameters());
    }

    public function test_create_booking_method_exists(): void
    {
        $this->assertTrue(
            method_exists(ConciergeService::class, 'createBooking'),
            'ConciergeService must implement createBooking()'
        );
    }

    public function test_get_eligible_offers_method_exists(): void
    {
        $this->assertTrue(
            method_exists(ConciergeService::class, 'getEligibleOffers'),
            'ConciergeService must implement getEligibleOffers()'
        );
    }
}
