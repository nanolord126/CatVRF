<?php declare(strict_types=1);

namespace Tests\Unit\Domains\Education;

use PHPUnit\Framework\TestCase;

/**
 * Unit tests for AILearningPathService.
 *
 * @covers \App\Domains\Education\Domain\Services\AILearningPathService
 */
final class AILearningPathServiceTest extends TestCase
{
    public function test_class_is_final(): void
    {
        $reflection = new \ReflectionClass(
            \App\Domains\Education\Domain\Services\AILearningPathService::class
        );
        $this->assertTrue($reflection->isFinal(), 'AILearningPathService must be final');
    }

    public function test_class_is_readonly(): void
    {
        $reflection = new \ReflectionClass(
            \App\Domains\Education\Domain\Services\AILearningPathService::class
        );
        $this->assertTrue($reflection->isReadOnly(), 'AILearningPathService must be readonly');
    }

    public function test_has_constructor_injection(): void
    {
        $reflection = new \ReflectionClass(
            \App\Domains\Education\Domain\Services\AILearningPathService::class
        );
        $constructor = $reflection->getConstructor();
        $this->assertNotNull($constructor, 'AILearningPathService must have __construct');
        $this->assertGreaterThan(0, $constructor->getNumberOfParameters());
    }

    public function test_constructPathForUser_method_exists(): void
    {
        $this->assertTrue(
            method_exists(\App\Domains\Education\Domain\Services\AILearningPathService::class, 'constructPathForUser'),
            'AILearningPathService must implement constructPathForUser()'
        );
    }

    public function test_enrollUserInSuggestedPath_method_exists(): void
    {
        $this->assertTrue(
            method_exists(\App\Domains\Education\Domain\Services\AILearningPathService::class, 'enrollUserInSuggestedPath'),
            'AILearningPathService must implement enrollUserInSuggestedPath()'
        );
    }

}
