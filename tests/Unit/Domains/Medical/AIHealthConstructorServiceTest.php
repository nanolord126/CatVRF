<?php declare(strict_types=1);

namespace Tests\Unit\Domains\Medical;

use PHPUnit\Framework\TestCase;

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
            \App\Domains\Medical\Domain\Services\AIHealthConstructorService::class
        );
        $this->assertTrue($reflection->isFinal(), 'AIHealthConstructorService must be final');
    }

    public function test_class_is_readonly(): void
    {
        $reflection = new \ReflectionClass(
            \App\Domains\Medical\Domain\Services\AIHealthConstructorService::class
        );
        $this->assertTrue($reflection->isReadOnly(), 'AIHealthConstructorService must be readonly');
    }

    public function test_has_constructor_injection(): void
    {
        $reflection = new \ReflectionClass(
            \App\Domains\Medical\Domain\Services\AIHealthConstructorService::class
        );
        $constructor = $reflection->getConstructor();
        $this->assertNotNull($constructor, 'AIHealthConstructorService must have __construct');
        $this->assertGreaterThan(0, $constructor->getNumberOfParameters());
    }

    public function test_matchSymptomToService_method_exists(): void
    {
        $this->assertTrue(
            method_exists(\App\Domains\Medical\Domain\Services\AIHealthConstructorService::class, 'matchSymptomToService'),
            'AIHealthConstructorService must implement matchSymptomToService()'
        );
    }

    public function test_analyzeVisionCondition_method_exists(): void
    {
        $this->assertTrue(
            method_exists(\App\Domains\Medical\Domain\Services\AIHealthConstructorService::class, 'analyzeVisionCondition'),
            'AIHealthConstructorService must implement analyzeVisionCondition()'
        );
    }

}
