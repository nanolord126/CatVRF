<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\VeganProducts;

use PHPUnit\Framework\TestCase;
use App\Domains\VeganProducts\Domain\Services\AIVeganConstructorService;

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
            AIVeganConstructorService::class
        );
        $this->assertTrue($reflection->isFinal(), 'AIVeganConstructorService must be final');
    }

    public function test_class_is_readonly(): void
    {
        $reflection = new \ReflectionClass(
            AIVeganConstructorService::class
        );
        $this->assertTrue($reflection->isReadOnly(), 'AIVeganConstructorService must be readonly');
    }

    public function test_has_constructor_injection(): void
    {
        $reflection = new \ReflectionClass(
            AIVeganConstructorService::class
        );
        $constructor = $reflection->getConstructor();
        $this->assertNotNull($constructor, 'AIVeganConstructorService must have __construct');
        $this->assertGreaterThan(0, $constructor->getNumberOfParameters());
    }

    public function test_generate_personalized_box_method_exists(): void
    {
        $this->assertTrue(
            method_exists(AIVeganConstructorService::class, 'generatePersonalizedBox'),
            'AIVeganConstructorService must implement generatePersonalizedBox()'
        );
    }
}
