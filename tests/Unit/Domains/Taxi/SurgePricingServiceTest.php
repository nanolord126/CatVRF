<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\Taxi;

use PHPUnit\Framework\TestCase;
use App\Domains\Taxi\Domain\Services\SurgePricingService;

/**
 * Unit tests for SurgePricingService.
 *
 * @covers \App\Domains\Taxi\Domain\Services\SurgePricingService
 */
final class SurgePricingServiceTest extends TestCase
{
    public function test_class_is_final(): void
    {
        $reflection = new \ReflectionClass(
            SurgePricingService::class
        );
        $this->assertTrue($reflection->isFinal(), 'SurgePricingService must be final');
    }

    public function test_class_is_readonly(): void
    {
        $reflection = new \ReflectionClass(
            SurgePricingService::class
        );
        $this->assertTrue($reflection->isReadOnly(), 'SurgePricingService must be readonly');
    }

    public function test_has_constructor_injection(): void
    {
        $reflection = new \ReflectionClass(
            SurgePricingService::class
        );
        $constructor = $reflection->getConstructor();
        $this->assertNotNull($constructor, 'SurgePricingService must have __construct');
        $this->assertGreaterThan(0, $constructor->getNumberOfParameters());
    }

    public function test_get_surge_multiplier_method_exists(): void
    {
        $this->assertTrue(
            method_exists(SurgePricingService::class, 'getSurgeMultiplier'),
            'SurgePricingService must implement getSurgeMultiplier()'
        );
    }

    public function test_recalculate_surges_method_exists(): void
    {
        $this->assertTrue(
            method_exists(SurgePricingService::class, 'recalculateSurges'),
            'SurgePricingService must implement recalculateSurges()'
        );
    }
}
