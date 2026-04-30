<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\Flowers;

use PHPUnit\Framework\TestCase;
use App\Domains\Flowers\Domain\Services\AIBouquetConstructorService;

/**
 * Unit tests for AIBouquetConstructorService.
 *
 * @covers \App\Domains\Flowers\Domain\Services\AIBouquetConstructorService
 */
final class AIBouquetConstructorServiceTest extends TestCase
{
    public function test_class_is_final(): void
    {
        $reflection = new \ReflectionClass(
            AIBouquetConstructorService::class
        );
        $this->assertTrue($reflection->isFinal(), 'AIBouquetConstructorService must be final');
    }

    public function test_class_is_readonly(): void
    {
        $reflection = new \ReflectionClass(
            AIBouquetConstructorService::class
        );
        $this->assertTrue($reflection->isReadOnly(), 'AIBouquetConstructorService must be readonly');
    }

    public function test_has_constructor_injection(): void
    {
        $reflection = new \ReflectionClass(
            AIBouquetConstructorService::class
        );
        $constructor = $reflection->getConstructor();
        $this->assertNotNull($constructor, 'AIBouquetConstructorService must have __construct');
        $this->assertGreaterThan(0, $constructor->getNumberOfParameters());
    }

    public function test_recommend_bouquet_method_exists(): void
    {
        $this->assertTrue(
            method_exists(AIBouquetConstructorService::class, 'recommendBouquet'),
            'AIBouquetConstructorService must implement recommendBouquet()'
        );
    }

    public function test_save_ai_recommendation_as_template_method_exists(): void
    {
        $this->assertTrue(
            method_exists(AIBouquetConstructorService::class, 'saveAIRecommendationAsTemplate'),
            'AIBouquetConstructorService must implement saveAIRecommendationAsTemplate()'
        );
    }

    public function test_analyze_bouquet_photo_method_exists(): void
    {
        $this->assertTrue(
            method_exists(AIBouquetConstructorService::class, 'analyzeBouquetPhoto'),
            'AIBouquetConstructorService must implement analyzeBouquetPhoto()'
        );
    }
}
