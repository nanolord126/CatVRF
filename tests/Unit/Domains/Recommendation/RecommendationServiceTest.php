<?php declare(strict_types=1);

namespace Tests\Unit\Domains\Recommendation;

use PHPUnit\Framework\TestCase;

/**
 * Unit tests for RecommendationService.
 *
 * @covers \App\Domains\Recommendation\Domain\Services\RecommendationService
 */
final class RecommendationServiceTest extends TestCase
{
    public function test_class_is_final(): void
    {
        $reflection = new \ReflectionClass(
            \App\Domains\Recommendation\Domain\Services\RecommendationService::class
        );
        $this->assertTrue($reflection->isFinal(), 'RecommendationService must be final');
    }

    public function test_class_is_readonly(): void
    {
        $reflection = new \ReflectionClass(
            \App\Domains\Recommendation\Domain\Services\RecommendationService::class
        );
        $this->assertTrue($reflection->isReadOnly(), 'RecommendationService must be readonly');
    }

    public function test_has_constructor_injection(): void
    {
        $reflection = new \ReflectionClass(
            \App\Domains\Recommendation\Domain\Services\RecommendationService::class
        );
        $constructor = $reflection->getConstructor();
        $this->assertNotNull($constructor, 'RecommendationService must have __construct');
        $this->assertGreaterThan(0, $constructor->getNumberOfParameters());
    }

    public function test_getForUser_method_exists(): void
    {
        $this->assertTrue(
            method_exists(\App\Domains\Recommendation\Domain\Services\RecommendationService::class, 'getForUser'),
            'RecommendationService must implement getForUser()'
        );
    }

    public function test_getCrossVertical_method_exists(): void
    {
        $this->assertTrue(
            method_exists(\App\Domains\Recommendation\Domain\Services\RecommendationService::class, 'getCrossVertical'),
            'RecommendationService must implement getCrossVertical()'
        );
    }

    public function test_invalidateUserCache_method_exists(): void
    {
        $this->assertTrue(
            method_exists(\App\Domains\Recommendation\Domain\Services\RecommendationService::class, 'invalidateUserCache'),
            'RecommendationService must implement invalidateUserCache()'
        );
    }

}
