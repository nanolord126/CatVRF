<?php declare(strict_types=1);

namespace Tests\Unit\Domains\EventPlanning;

use PHPUnit\Framework\TestCase;

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
            \App\Domains\EventPlanning\Domain\Services\EventAIService::class
        );
        $this->assertTrue($reflection->isFinal(), 'EventAIService must be final');
    }

    public function test_class_is_readonly(): void
    {
        $reflection = new \ReflectionClass(
            \App\Domains\EventPlanning\Domain\Services\EventAIService::class
        );
        $this->assertTrue($reflection->isReadOnly(), 'EventAIService must be readonly');
    }

    public function test_has_constructor_injection(): void
    {
        $reflection = new \ReflectionClass(
            \App\Domains\EventPlanning\Domain\Services\EventAIService::class
        );
        $constructor = $reflection->getConstructor();
        $this->assertNotNull($constructor, 'EventAIService must have __construct');
        $this->assertGreaterThan(0, $constructor->getNumberOfParameters());
    }

    public function test_generateEventPlan_method_exists(): void
    {
        $this->assertTrue(
            method_exists(\App\Domains\EventPlanning\Domain\Services\EventAIService::class, 'generateEventPlan'),
            'EventAIService must implement generateEventPlan()'
        );
    }

}
