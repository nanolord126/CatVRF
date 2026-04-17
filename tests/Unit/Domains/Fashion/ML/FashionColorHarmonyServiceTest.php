<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\Fashion\ML;

use Modules\Fashion\Services\ML\FashionColorHarmonyService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class FashionColorHarmonyServiceTest extends TestCase
{
    use RefreshDatabase;

    private FashionColorHarmonyService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(FashionColorHarmonyService::class);
    }

    public function test_get_recommendations_from_beauty_history(): void
    {
        $result = $this->service->getRecommendationsFromBeautyHistory(1, 1);

        $this->assertIsArray($result);
    }

    public function test_get_photo_session_outfit_suggestions(): void
    {
        $result = $this->service->getPhotoSessionOutfitSuggestions(1, 1, 'formal');

        $this->assertIsArray($result);
    }

    public function test_photo_session_outfit_general_occasion(): void
    {
        $result = $this->service->getPhotoSessionOutfitSuggestions(1, 1, 'general');

        $this->assertIsArray($result);
    }

    public function test_photo_session_outfit_casual_occasion(): void
    {
        $result = $this->service->getPhotoSessionOutfitSuggestions(1, 1, 'casual');

        $this->assertIsArray($result);
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

    public function test_get_complementary_colors_for_blonde_hair(): void
    {
        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('getComplementaryColorsForHair');
        $method->setAccessible(true);

        $result = $method->invoke($this->service, 'blonde');

        $this->assertIsArray($result);
        $this->assertContains('blue', $result);
        $this->assertContains('green', $result);
    }
}
