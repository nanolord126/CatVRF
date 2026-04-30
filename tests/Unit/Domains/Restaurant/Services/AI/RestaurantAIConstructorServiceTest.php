<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\Restaurant\Services\AI;

use PHPUnit\Framework\TestCase;
use App\Domains\Restaurant\Services\AI\RestaurantAIConstructorService;

/**
 * Unit tests for RestaurantAIConstructorService.
 *
 * @covers \App\Domains\Restaurant\Services\AI\RestaurantAIConstructorService
 */
final class RestaurantAIConstructorServiceTest extends TestCase
{
    public function test_class_exists(): void
    {
        $this->assertTrue(class_exists(RestaurantAIConstructorService::class));
    }

    public function test_has_generate_restaurant_description_method(): void
    {
        $this->assertTrue(
            method_exists(RestaurantAIConstructorService::class, 'generateRestaurantDescription'),
            'RestaurantAIConstructorService must have generateRestaurantDescription method'
        );
    }

    public function test_has_suggest_menu_items_method(): void
    {
        $this->assertTrue(
            method_exists(RestaurantAIConstructorService::class, 'suggestMenuItems'),
            'RestaurantAIConstructorService must have suggestMenuItems method'
        );
    }

    public function test_has_analyze_restaurant_trends_method(): void
    {
        $this->assertTrue(
            method_exists(RestaurantAIConstructorService::class, 'analyzeRestaurantTrends'),
            'RestaurantAIConstructorService must have analyzeRestaurantTrends method'
        );
    }
}
