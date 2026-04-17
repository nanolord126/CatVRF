<?php declare(strict_types=1);

namespace Tests\Unit\Domains\Restaurant\Services\AI;

use PHPUnit\Framework\TestCase;

/**
 * Unit tests for RestaurantAIConstructorService.
 *
 * @covers \App\Domains\Restaurant\Services\AI\RestaurantAIConstructorService
 */
final class RestaurantAIConstructorServiceTest extends TestCase
{
    public function test_class_exists(): void
    {
        $this->assertTrue(class_exists(\App\Domains\Restaurant\Services\AI\RestaurantAIConstructorService::class));
    }

    public function test_has_generateRestaurantDescription_method(): void
    {
        $this->assertTrue(
            method_exists(\App\Domains\Restaurant\Services\AI\RestaurantAIConstructorService::class, 'generateRestaurantDescription'),
            'RestaurantAIConstructorService must have generateRestaurantDescription method'
        );
    }

    public function test_has_suggestMenuItems_method(): void
    {
        $this->assertTrue(
            method_exists(\App\Domains\Restaurant\Services\AI\RestaurantAIConstructorService::class, 'suggestMenuItems'),
            'RestaurantAIConstructorService must have suggestMenuItems method'
        );
    }

    public function test_has_analyzeRestaurantTrends_method(): void
    {
        $this->assertTrue(
            method_exists(\App\Domains\Restaurant\Services\AI\RestaurantAIConstructorService::class, 'analyzeRestaurantTrends'),
            'RestaurantAIConstructorService must have analyzeRestaurantTrends method'
        );
    }
}
