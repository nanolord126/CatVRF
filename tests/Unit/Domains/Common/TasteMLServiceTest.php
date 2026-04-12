<?php declare(strict_types=1);

namespace Tests\Unit\Domains\Common;

use PHPUnit\Framework\TestCase;

/**
 * Unit tests for TasteMLService.
 *
 * @covers \App\Domains\Common\Domain\Services\TasteMLService
 */
final class TasteMLServiceTest extends TestCase
{
    public function test_class_is_final(): void
    {
        $reflection = new \ReflectionClass(
            \App\Domains\Common\Domain\Services\TasteMLService::class
        );
        $this->assertTrue($reflection->isFinal(), 'TasteMLService must be final');
    }

    public function test_class_is_readonly(): void
    {
        $reflection = new \ReflectionClass(
            \App\Domains\Common\Domain\Services\TasteMLService::class
        );
        $this->assertTrue($reflection->isReadOnly(), 'TasteMLService must be readonly');
    }

    public function test_has_constructor_injection(): void
    {
        $reflection = new \ReflectionClass(
            \App\Domains\Common\Domain\Services\TasteMLService::class
        );
        $constructor = $reflection->getConstructor();
        $this->assertNotNull($constructor, 'TasteMLService must have __construct');
        $this->assertGreaterThan(0, $constructor->getNumberOfParameters());
    }

    public function test_cosineSimilarity_method_exists(): void
    {
        $this->assertTrue(
            method_exists(\App\Domains\Common\Domain\Services\TasteMLService::class, 'cosineSimilarity'),
            'TasteMLService must implement cosineSimilarity()'
        );
    }

    public function test_getRecommendationsForUser_method_exists(): void
    {
        $this->assertTrue(
            method_exists(\App\Domains\Common\Domain\Services\TasteMLService::class, 'getRecommendationsForUser'),
            'TasteMLService must implement getRecommendationsForUser()'
        );
    }

    public function test_recalculateProfileEmbedding_method_exists(): void
    {
        $this->assertTrue(
            method_exists(\App\Domains\Common\Domain\Services\TasteMLService::class, 'recalculateProfileEmbedding'),
            'TasteMLService must implement recalculateProfileEmbedding()'
        );
    }

    public function test_updateCTR_method_exists(): void
    {
        $this->assertTrue(
            method_exists(\App\Domains\Common\Domain\Services\TasteMLService::class, 'updateCTR'),
            'TasteMLService must implement updateCTR()'
        );
    }

    public function test_updateAcceptanceRate_method_exists(): void
    {
        $this->assertTrue(
            method_exists(\App\Domains\Common\Domain\Services\TasteMLService::class, 'updateAcceptanceRate'),
            'TasteMLService must implement updateAcceptanceRate()'
        );
    }

}
