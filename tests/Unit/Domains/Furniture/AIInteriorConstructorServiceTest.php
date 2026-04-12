<?php declare(strict_types=1);

namespace Tests\Unit\Domains\Furniture;

use PHPUnit\Framework\TestCase;

/**
 * Unit tests for AIInteriorConstructorService.
 *
 * @covers \App\Domains\Furniture\Domain\Services\AIInteriorConstructorService
 */
final class AIInteriorConstructorServiceTest extends TestCase
{
    public function test_class_is_final(): void
    {
        $reflection = new \ReflectionClass(
            \App\Domains\Furniture\Domain\Services\AIInteriorConstructorService::class
        );
        $this->assertTrue($reflection->isFinal(), 'AIInteriorConstructorService must be final');
    }

    public function test_class_is_readonly(): void
    {
        $reflection = new \ReflectionClass(
            \App\Domains\Furniture\Domain\Services\AIInteriorConstructorService::class
        );
        $this->assertTrue($reflection->isReadOnly(), 'AIInteriorConstructorService must be readonly');
    }

    public function test_has_constructor_injection(): void
    {
        $reflection = new \ReflectionClass(
            \App\Domains\Furniture\Domain\Services\AIInteriorConstructorService::class
        );
        $constructor = $reflection->getConstructor();
        $this->assertNotNull($constructor, 'AIInteriorConstructorService must have __construct');
        $this->assertGreaterThan(0, $constructor->getNumberOfParameters());
    }

    public function test_generateInteriorSetup_method_exists(): void
    {
        $this->assertTrue(
            method_exists(\App\Domains\Furniture\Domain\Services\AIInteriorConstructorService::class, 'generateInteriorSetup'),
            'AIInteriorConstructorService must implement generateInteriorSetup()'
        );
    }

}
