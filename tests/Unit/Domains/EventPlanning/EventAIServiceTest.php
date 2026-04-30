<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\EventPlanning;

use PHPUnit\Framework\TestCase;
use App\Domains\EventPlanning\Domain\Services\EventAIService;

/**
 * Unit tests for EventAIService.
 *
 * @covers \App\Domains\EventPlanning\Domain\Services\EventAIService
 */
final class EventAIServiceTest extends TestCase
{
    public function test_class_is_final(): void
    {
        $reflection = new \ReflectionClass(
            EventAIService::class
        );
        $this->assertTrue($reflection->isFinal(), 'EventAIService must be final');
    }

    public function test_class_is_readonly(): void
    {
        $reflection = new \ReflectionClass(
            EventAIService::class
        );
        $this->assertTrue($reflection->isReadOnly(), 'EventAIService must be readonly');
    }

    public function test_has_constructor_injection(): void
    {
        $reflection = new \ReflectionClass(
            EventAIService::class
        );
        $constructor = $reflection->getConstructor();
        $this->assertNotNull($constructor, 'EventAIService must have __construct');
        $this->assertGreaterThan(0, $constructor->getNumberOfParameters());
    }

    public function test_generate_event_plan_method_exists(): void
    {
        $this->assertTrue(
            method_exists(EventAIService::class, 'generateEventPlan'),
            'EventAIService must implement generateEventPlan()'
        );
    }
}
