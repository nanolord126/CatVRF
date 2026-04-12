<?php declare(strict_types=1);

namespace Tests\Unit\Domains\VeganProducts;

use PHPUnit\Framework\TestCase;

/**
 * Unit tests for AIVeganConstructorService.
 *
 * @covers \App\Domains\VeganProducts\Domain\Services\AIVeganConstructorService
 */
final class AIVeganConstructorServiceTest extends TestCase
{
    public function test_class_is_final(): void
    {
        $reflection = new \ReflectionClass(
            \App\Domains\VeganProducts\Domain\Services\AIVeganConstructorService::class
        );
        $this->assertTrue($reflection->isFinal(), 'AIVeganConstructorService must be final');
    }

    public function test_class_is_readonly(): void
    {
        $reflection = new \ReflectionClass(
            \App\Domains\VeganProducts\Domain\Services\AIVeganConstructorService::class
        );
        $this->assertTrue($reflection->isReadOnly(), 'AIVeganConstructorService must be readonly');
    }

    public function test_has_constructor_injection(): void
    {
        $reflection = new \ReflectionClass(
            \App\Domains\VeganProducts\Domain\Services\AIVeganConstructorService::class
        );
        $constructor = $reflection->getConstructor();
        $this->assertNotNull($constructor, 'AIVeganConstructorService must have __construct');
        $this->assertGreaterThan(0, $constructor->getNumberOfParameters());
    }

    public function test_generatePersonalizedBox_method_exists(): void
    {
        $this->assertTrue(
            method_exists(\App\Domains\VeganProducts\Domain\Services\AIVeganConstructorService::class, 'generatePersonalizedBox'),
            'AIVeganConstructorService must implement generatePersonalizedBox()'
        );
    }

}
