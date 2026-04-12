<?php declare(strict_types=1);

namespace Tests\Unit\Domains\Recommendation;

use PHPUnit\Framework\TestCase;

/**
 * Unit tests for RecommendationCoordinatorService.
 *
 * @covers \App\Domains\Recommendation\Domain\Services\RecommendationCoordinatorService
 */
final class RecommendationCoordinatorServiceTest extends TestCase
{
    public function test_class_is_final(): void
    {
        $reflection = new \ReflectionClass(
            \App\Domains\Recommendation\Domain\Services\RecommendationCoordinatorService::class
        );
        $this->assertTrue($reflection->isFinal(), 'RecommendationCoordinatorService must be final');
    }

    public function test_class_is_readonly(): void
    {
        $reflection = new \ReflectionClass(
            \App\Domains\Recommendation\Domain\Services\RecommendationCoordinatorService::class
        );
        $this->assertTrue($reflection->isReadOnly(), 'RecommendationCoordinatorService must be readonly');
    }

    public function test_has_constructor_injection(): void
    {
        $reflection = new \ReflectionClass(
            \App\Domains\Recommendation\Domain\Services\RecommendationCoordinatorService::class
        );
        $constructor = $reflection->getConstructor();
        $this->assertNotNull($constructor, 'RecommendationCoordinatorService must have __construct');
        $this->assertGreaterThan(0, $constructor->getNumberOfParameters());
    }

    public function test_create_method_exists(): void
    {
        $this->assertTrue(
            method_exists(\App\Domains\Recommendation\Domain\Services\RecommendationCoordinatorService::class, 'create'),
            'RecommendationCoordinatorService must implement create()'
        );
    }

    public function test_update_method_exists(): void
    {
        $this->assertTrue(
            method_exists(\App\Domains\Recommendation\Domain\Services\RecommendationCoordinatorService::class, 'update'),
            'RecommendationCoordinatorService must implement update()'
        );
    }

    public function test_delete_method_exists(): void
    {
        $this->assertTrue(
            method_exists(\App\Domains\Recommendation\Domain\Services\RecommendationCoordinatorService::class, 'delete'),
            'RecommendationCoordinatorService must implement delete()'
        );
    }

    public function test_list_method_exists(): void
    {
        $this->assertTrue(
            method_exists(\App\Domains\Recommendation\Domain\Services\RecommendationCoordinatorService::class, 'list'),
            'RecommendationCoordinatorService must implement list()'
        );
    }

    public function test_getById_method_exists(): void
    {
        $this->assertTrue(
            method_exists(\App\Domains\Recommendation\Domain\Services\RecommendationCoordinatorService::class, 'getById'),
            'RecommendationCoordinatorService must implement getById()'
        );
    }

}
