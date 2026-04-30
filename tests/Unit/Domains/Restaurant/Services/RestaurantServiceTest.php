<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\Restaurant\Services;

use PHPUnit\Framework\TestCase;
use App\Domains\Restaurant\Services\RestaurantService;

/**
 * Unit tests for RestaurantService.
 *
 * @covers \App\Domains\Restaurant\Services\RestaurantService
 */
final class RestaurantServiceTest extends TestCase
{
    public function test_class_exists(): void
    {
        $this->assertTrue(class_exists(RestaurantService::class));
    }

    public function test_has_create_restaurant_method(): void
    {
        $this->assertTrue(
            method_exists(RestaurantService::class, 'createRestaurant'),
            'RestaurantService must have createRestaurant method'
        );
    }

    public function test_has_get_restaurant_by_id_method(): void
    {
        $this->assertTrue(
            method_exists(RestaurantService::class, 'getRestaurantById'),
            'RestaurantService must have getRestaurantById method'
        );
    }

    public function test_has_get_restaurants_by_category_method(): void
    {
        $this->assertTrue(
            method_exists(RestaurantService::class, 'getRestaurantsByCategory'),
            'RestaurantService must have getRestaurantsByCategory method'
        );
    }

    public function test_has_get_restaurants_by_cuisine_method(): void
    {
        $this->assertTrue(
            method_exists(RestaurantService::class, 'getRestaurantsByCuisine'),
            'RestaurantService must have getRestaurantsByCuisine method'
        );
    }

    public function test_has_search_restaurants_method(): void
    {
        $this->assertTrue(
            method_exists(RestaurantService::class, 'searchRestaurants'),
            'RestaurantService must have searchRestaurants method'
        );
    }

    public function test_has_get_nearby_restaurants_method(): void
    {
        $this->assertTrue(
            method_exists(RestaurantService::class, 'getNearbyRestaurants'),
            'RestaurantService must have getNearbyRestaurants method'
        );
    }
}
