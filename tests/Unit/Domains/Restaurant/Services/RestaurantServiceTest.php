<?php declare(strict_types=1);

namespace Tests\Unit\Domains\Restaurant\Services;

use PHPUnit\Framework\TestCase;

/**
 * Unit tests for RestaurantService.
 *
 * @covers \App\Domains\Restaurant\Services\RestaurantService
 */
final class RestaurantServiceTest extends TestCase
{
    public function test_class_exists(): void
    {
        $this->assertTrue(class_exists(\App\Domains\Restaurant\Services\RestaurantService::class));
    }

    public function test_has_createRestaurant_method(): void
    {
        $this->assertTrue(
            method_exists(\App\Domains\Restaurant\Services\RestaurantService::class, 'createRestaurant'),
            'RestaurantService must have createRestaurant method'
        );
    }

    public function test_has_getRestaurantById_method(): void
    {
        $this->assertTrue(
            method_exists(\App\Domains\Restaurant\Services\RestaurantService::class, 'getRestaurantById'),
            'RestaurantService must have getRestaurantById method'
        );
    }

    public function test_has_getRestaurantsByCategory_method(): void
    {
        $this->assertTrue(
            method_exists(\App\Domains\Restaurant\Services\RestaurantService::class, 'getRestaurantsByCategory'),
            'RestaurantService must have getRestaurantsByCategory method'
        );
    }

    public function test_has_getRestaurantsByCuisine_method(): void
    {
        $this->assertTrue(
            method_exists(\App\Domains\Restaurant\Services\RestaurantService::class, 'getRestaurantsByCuisine'),
            'RestaurantService must have getRestaurantsByCuisine method'
        );
    }

    public function test_has_searchRestaurants_method(): void
    {
        $this->assertTrue(
            method_exists(\App\Domains\Restaurant\Services\RestaurantService::class, 'searchRestaurants'),
            'RestaurantService must have searchRestaurants method'
        );
    }

    public function test_has_getNearbyRestaurants_method(): void
    {
        $this->assertTrue(
            method_exists(\App\Domains\Restaurant\Services\RestaurantService::class, 'getNearbyRestaurants'),
            'RestaurantService must have getNearbyRestaurants method'
        );
    }
}
