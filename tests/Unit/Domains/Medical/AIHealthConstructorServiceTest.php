<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\Medical;

use PHPUnit\Framework\TestCase;
use App\Domains\Medical\Domain\Services\AIHealthConstructorService;

/**
 * Unit tests for AIHealthConstructorService.
 *
 * @covers \App\Domains\Medical\Domain\Services\AIHealthConstructorService
 */
final class AIHealthConstructorServiceTest extends TestCase
{
    public function test_class_is_final(): void
    {
        $reflection = new \ReflectionClass(
            AIHealthConstructorService::class
        );
        $this->assertTrue($reflection->isFinal(), 'AIHealthConstructorService must be final');
    }

    public function test_class_is_readonly(): void
    {
        $reflection = new \ReflectionClass(
            AIHealthConstructorService::class
        );
        $this->assertTrue($reflection->isReadOnly(), 'AIHealthConstructorService must be readonly');
    }

    public function test_has_constructor_injection(): void
    {
        $reflection = new \ReflectionClass(
            AIHealthConstructorService::class
        );
        $constructor = $reflection->getConstructor();
        $this->assertNotNull($constructor, 'AIHealthConstructorService must have __construct');
        $this->assertGreaterThan(0, $constructor->getNumberOfParameters());
    }

    public function test_match_symptom_to_service_method_exists(): void
    {
        $this->assertTrue(
            method_exists(AIHealthConstructorService::class, 'matchSymptomToService'),
            'AIHealthConstructorService must implement matchSymptomToService()'
        );
    }

    public function test_analyze_vision_condition_method_exists(): void
    {
        $this->assertTrue(
            method_exists(AIHealthConstructorService::class, 'analyzeVisionCondition'),
            'AIHealthConstructorService must implement analyzeVisionCondition()'
        );
    }
}
