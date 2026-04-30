<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\Common;

use PHPUnit\Framework\TestCase;
use App\Domains\Common\Domain\Services\TasteMLService;

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
            TasteMLService::class
        );
        $this->assertTrue($reflection->isFinal(), 'TasteMLService must be final');
    }

    public function test_class_is_readonly(): void
    {
        $reflection = new \ReflectionClass(
            TasteMLService::class
        );
        $this->assertTrue($reflection->isReadOnly(), 'TasteMLService must be readonly');
    }

    public function test_has_constructor_injection(): void
    {
        $reflection = new \ReflectionClass(
            TasteMLService::class
        );
        $constructor = $reflection->getConstructor();
        $this->assertNotNull($constructor, 'TasteMLService must have __construct');
        $this->assertGreaterThan(0, $constructor->getNumberOfParameters());
    }

    public function test_cosine_similarity_method_exists(): void
    {
        $this->assertTrue(
            method_exists(TasteMLService::class, 'cosineSimilarity'),
            'TasteMLService must implement cosineSimilarity()'
        );
    }

    public function test_get_recommendations_for_user_method_exists(): void
    {
        $this->assertTrue(
            method_exists(TasteMLService::class, 'getRecommendationsForUser'),
            'TasteMLService must implement getRecommendationsForUser()'
        );
    }

    public function test_recalculate_profile_embedding_method_exists(): void
    {
        $this->assertTrue(
            method_exists(TasteMLService::class, 'recalculateProfileEmbedding'),
            'TasteMLService must implement recalculateProfileEmbedding()'
        );
    }

    public function test_update_ct_r_method_exists(): void
    {
        $this->assertTrue(
            method_exists(TasteMLService::class, 'updateCTR'),
            'TasteMLService must implement updateCTR()'
        );
    }

    public function test_update_acceptance_rate_method_exists(): void
    {
        $this->assertTrue(
            method_exists(TasteMLService::class, 'updateAcceptanceRate'),
            'TasteMLService must implement updateAcceptanceRate()'
        );
    }
}
