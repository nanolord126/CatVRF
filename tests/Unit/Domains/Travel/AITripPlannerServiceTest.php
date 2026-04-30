<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\Travel;

use PHPUnit\Framework\TestCase;
use App\Domains\Travel\Domain\Services\AITripPlannerService;

/**
 * Unit tests for AITripPlannerService.
 *
 * @covers \App\Domains\Travel\Domain\Services\AITripPlannerService
 */
final class AITripPlannerServiceTest extends TestCase
{
    public function test_class_is_final(): void
    {
        $reflection = new \ReflectionClass(
            AITripPlannerService::class
        );
        $this->assertTrue($reflection->isFinal(), 'AITripPlannerService must be final');
    }

    public function test_class_is_readonly(): void
    {
        $reflection = new \ReflectionClass(
            AITripPlannerService::class
        );
        $this->assertTrue($reflection->isReadOnly(), 'AITripPlannerService must be readonly');
    }

    public function test_has_constructor_injection(): void
    {
        $reflection = new \ReflectionClass(
            AITripPlannerService::class
        );
        $constructor = $reflection->getConstructor();
        $this->assertNotNull($constructor, 'AITripPlannerService must have __construct');
        $this->assertGreaterThan(0, $constructor->getNumberOfParameters());
    }

    public function test_generate_trip_plan_method_exists(): void
    {
        $this->assertTrue(
            method_exists(AITripPlannerService::class, 'generateTripPlan'),
            'AITripPlannerService must implement generateTripPlan()'
        );
    }
}
