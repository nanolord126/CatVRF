<?php declare(strict_types=1);

namespace Tests\Unit\Domains\FraudML;

use PHPUnit\Framework\TestCase;

/**
 * Unit tests for FraudMLService.
 *
 * @covers \App\Domains\FraudML\Domain\Services\FraudMLService
 */
final class FraudMLServiceTest extends TestCase
{
    public function test_class_is_final(): void
    {
        $reflection = new \ReflectionClass(
            \App\Domains\FraudML\Domain\Services\FraudMLService::class
        );
        $this->assertTrue($reflection->isFinal(), 'FraudMLService must be final');
    }

    public function test_class_is_readonly(): void
    {
        $reflection = new \ReflectionClass(
            \App\Domains\FraudML\Domain\Services\FraudMLService::class
        );
        $this->assertTrue($reflection->isReadOnly(), 'FraudMLService must be readonly');
    }

    public function test_has_constructor_injection(): void
    {
        $reflection = new \ReflectionClass(
            \App\Domains\FraudML\Domain\Services\FraudMLService::class
        );
        $constructor = $reflection->getConstructor();
        $this->assertNotNull($constructor, 'FraudMLService must have __construct');
        $this->assertGreaterThan(0, $constructor->getNumberOfParameters());
    }

    public function test_scoreOperation_method_exists(): void
    {
        $this->assertTrue(
            method_exists(\App\Domains\FraudML\Domain\Services\FraudMLService::class, 'scoreOperation'),
            'FraudMLService must implement scoreOperation()'
        );
    }

    public function test_shouldBlock_method_exists(): void
    {
        $this->assertTrue(
            method_exists(\App\Domains\FraudML\Domain\Services\FraudMLService::class, 'shouldBlock'),
            'FraudMLService must implement shouldBlock()'
        );
    }

}
