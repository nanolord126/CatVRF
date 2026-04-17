<?php declare(strict_types=1);

namespace Tests\Unit\Domains\Fashion;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class FashionPhotoCriteriaTest extends TestCase
{
    use RefreshDatabase;

    public function test_fashion_config_has_photo_quality_criteria(): void
    {
        $config = config('fashion.photo_quality');
        
        $this->assertIsArray($config);
        $this->assertArrayHasKey('min_resolution', $config);
        $this->assertArrayHasKey('recommended_resolution', $config);
        $this->assertArrayHasKey('optimal_resolution', $config);
        $this->assertArrayHasKey('aspect_ratios', $config);
        $this->assertArrayHasKey('file_size', $config);
        $this->assertArrayHasKey('formats', $config);
        $this->assertArrayHasKey('quality_weights', $config);
    }

    public function test_min_resolution_meets_fashion_standards(): void
    {
        $minResolution = config('fashion.photo_quality.min_resolution');
        
        $this->assertGreaterThanOrEqual(800, $minResolution['width']);
        $this->assertGreaterThanOrEqual(800, $minResolution['height']);
    }

    public function test_recommended_resolution_is_higher_than_minimum(): void
    {
        $minResolution = config('fashion.photo_quality.min_resolution');
        $recommendedResolution = config('fashion.photo_quality.recommended_resolution');
        
        $this->assertGreaterThanOrEqual($minResolution['width'], $recommendedResolution['width']);
        $this->assertGreaterThanOrEqual($minResolution['height'], $recommendedResolution['height']);
    }

    public function test_aspect_ratio_includes_square(): void
    {
        $aspectRatios = config('fashion.photo_quality.aspect_ratios');
        
        $this->assertEquals(1.0, $aspectRatios['ideal']);
        $this->assertArrayHasKey('acceptable_min', $aspectRatios);
        $this->assertArrayHasKey('acceptable_max', $aspectRatios);
    }

    public function test_file_size_limits_are_reasonable(): void
    {
        $fileSize = config('fashion.photo_quality.file_size');
        
        $this->assertGreaterThanOrEqual(50, $fileSize['min']);
        $this->assertLessThanOrEqual(10240, $fileSize['hard_max']);
        $this->assertGreaterThan($fileSize['min'], $fileSize['recommended_min']);
    }

    public function test_supported_formats_include_modern_formats(): void
    {
        $formats = config('fashion.photo_quality.formats');
        
        $this->assertArrayHasKey('webp', $formats);
        $this->assertArrayHasKey('jpeg', $formats);
        $this->assertArrayHasKey('png', $formats);
        
        $this->assertEquals('excellent', $formats['webp']['priority']);
    }

    public function test_forbidden_formats_are_configured(): void
    {
        $forbiddenFormats = config('fashion.photo_quality.forbidden_formats');
        
        $this->assertIsArray($forbiddenFormats);
        $this->assertContains('bmp', $forbiddenFormats);
        $this->assertContains('tiff', $forbiddenFormats);
    }

    public function test_quality_weights_sum_to_100(): void
    {
        $weights = config('fashion.photo_quality.quality_weights');
        
        $totalWeight = array_sum($weights);
        $this->assertEquals(100, $totalWeight);
    }

    public function test_quality_thresholds_are_descending(): void
    {
        $thresholds = config('fashion.photo_quality.quality_thresholds');
        
        $this->assertGreaterThan($thresholds['acceptable'], $thresholds['good']);
        $this->assertGreaterThan($thresholds['good'], $thresholds['excellent']);
    }

    public function test_category_specific_criteria_exist(): void
    {
        $categorySpecific = config('fashion.photo_quality.category_specific');
        
        $this->assertArrayHasKey('clothing', $categorySpecific);
        $this->assertArrayHasKey('shoes', $categorySpecific);
        $this->assertArrayHasKey('accessories', $categorySpecific);
        $this->assertArrayHasKey('underwear', $categorySpecific);
    }

    public function test_clothing_category_has_stricter_requirements(): void
    {
        $clothing = config('fashion.photo_quality.category_specific.clothing');
        $accessories = config('fashion.photo_quality.category_specific.accessories');
        
        $this->assertGreaterThanOrEqual(
            $accessories['min_resolution']['width'],
            $clothing['min_resolution']['width']
        );
    }

    public function test_product_card_config_exists(): void
    {
        $productCard = config('fashion.product_card');
        
        $this->assertIsArray($productCard);
        $this->assertArrayHasKey('show_quality_badge', $productCard);
        $this->assertArrayHasKey('show_discount', $productCard);
        $this->assertArrayHasKey('show_stock', $productCard);
        $this->assertArrayHasKey('image_sizes', $productCard);
    }

    public function test_image_sizes_are_configured(): void
    {
        $imageSizes = config('fashion.product_card.image_sizes');
        
        $this->assertArrayHasKey('thumbnail', $imageSizes);
        $this->assertArrayHasKey('card', $imageSizes);
        $this->assertArrayHasKey('detail', $imageSizes);
        $this->assertArrayHasKey('zoom', $imageSizes);
        
        $this->assertGreaterThan($imageSizes['thumbnail']['width'], $imageSizes['card']['width']);
        $this->assertGreaterThan($imageSizes['card']['width'], $imageSizes['detail']['width']);
    }

    public function test_validation_messages_are_configured(): void
    {
        $messages = config('fashion.validation_messages');
        
        $this->assertIsArray($messages);
        $this->assertArrayHasKey('resolution_too_low', $messages);
        $this->assertArrayHasKey('aspect_ratio_poor', $messages);
        $this->assertArrayHasKey('file_size_too_large', $messages);
        $this->assertArrayHasKey('format_not_supported', $messages);
    }

    public function test_validation_messages_contain_placeholders(): void
    {
        $messages = config('fashion.validation_messages');
        
        $this->assertStringContainsString(':min_width', $messages['resolution_too_low']);
        $this->assertStringContainsString(':max', $messages['file_size_too_large']);
        $this->assertStringContainsString(':format', $messages['format_not_supported']);
    }
}
