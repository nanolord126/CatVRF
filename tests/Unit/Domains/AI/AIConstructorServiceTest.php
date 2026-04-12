<?php declare(strict_types=1);

namespace Tests\Unit\Domains\AI;

use PHPUnit\Framework\TestCase;

/**
 * Unit tests for AIConstructorService.
 *
 * @covers \App\Domains\AI\Domain\Services\AIConstructorService
 */
final class AIConstructorServiceTest extends TestCase
{
    public function test_class_is_final(): void
    {
        $reflection = new \ReflectionClass(
            \App\Domains\AI\Domain\Services\AIConstructorService::class
        );
        $this->assertTrue($reflection->isFinal(), 'AIConstructorService must be final');
    }

    public function test_class_is_readonly(): void
    {
        $reflection = new \ReflectionClass(
            \App\Domains\AI\Domain\Services\AIConstructorService::class
        );
        $this->assertTrue($reflection->isReadOnly(), 'AIConstructorService must be readonly');
    }

    public function test_has_constructor_injection(): void
    {
        $reflection = new \ReflectionClass(
            \App\Domains\AI\Domain\Services\AIConstructorService::class
        );
        $constructor = $reflection->getConstructor();
        $this->assertNotNull($constructor, 'AIConstructorService must have __construct');
        $this->assertGreaterThan(0, $constructor->getNumberOfParameters());
    }

    public function test_analyzePhotoAndRecommend_method_exists(): void
    {
        $this->assertTrue(
            method_exists(\App\Domains\AI\Domain\Services\AIConstructorService::class, 'analyzePhotoAndRecommend'),
            'AIConstructorService must implement analyzePhotoAndRecommend()'
        );
    }

}
