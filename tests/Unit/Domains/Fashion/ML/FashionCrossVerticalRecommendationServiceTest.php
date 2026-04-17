<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\Fashion\ML;

use Modules\Fashion\Services\ML\FashionCrossVerticalRecommendationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class FashionCrossVerticalRecommendationServiceTest extends TestCase
{
    use RefreshDatabase;

    private FashionCrossVerticalRecommendationService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(FashionCrossVerticalRecommendationService::class);
    }

    public function test_get_beauty_to_fashion_recommendations(): void
    {
        $result = $this->service->getBeautyToFashionRecommendations(1, 1, 10);

        $this->assertIsArray($result);
    }

    public function test_get_beauty_to_fashion_recommendations_with_custom_limit(): void
    {
        $result = $this->service->getBeautyToFashionRecommendations(1, 1, 5);

        $this->assertIsArray($result);
        $this->assertLessThanOrEqual(5, count($result));
    }

    public function test_get_wardrobe_update_suggestions(): void
    {
        $result = $this->service->getWardrobeUpdateSuggestions(1, 1);

        $this->assertIsArray($result);
    }

    public function test_sort_and_limit_recommendations(): void
    {
        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('sortAndLimit');
        $method->setAccessible(true);

        $recommendations = [
            ['product' => ['id' => 1], 'relevance_score' => 0.5],
            ['product' => ['id' => 2], 'relevance_score' => 0.9],
            ['product' => ['id' => 3], 'relevance_score' => 0.7],
        ];

        $result = $method->invoke($this->service, $recommendations, 2);

        $this->assertIsArray($result);
        $this->assertLessThanOrEqual(2, count($result));
        $this->assertEquals(0.9, $result[0]['relevance_score']);
    }

    public function test_extract_color_from_service_name(): void
    {
        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('extractColor');
        $method->setAccessible(true);

        $result = $method->invoke($this->service, 'Red Nail Polish');

        $this->assertEquals('red', $result);
    }

    public function test_extract_hair_type_from_service_name(): void
    {
        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('extractHairType');
        $method->setAccessible(true);

        $result = $method->invoke($this->service, 'Blonde Hair Coloring');

        $this->assertEquals('blonde', $result);
    }

    public function test_get_complementary_colors_for_hair(): void
    {
        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('getComplementaryColorsForHair');
        $method->setAccessible(true);

        $result = $method->invoke($this->service, 'blonde');

        $this->assertIsArray($result);
        $this->assertNotEmpty($result);
    }
}
