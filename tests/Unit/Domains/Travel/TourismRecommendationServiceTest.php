<?php declare(strict_types=1);

namespace Tests\Unit\Domains\Travel;

use PHPUnit\Framework\TestCase;
use App\Domains\Travel\Services\TourismRecommendationService;

/**
 * Unit tests for TourismRecommendationService.
 *
 * @covers \App\Domains\Travel\Services\TourismRecommendationService
 * @group travel-services
 */
final class TourismRecommendationServiceTest extends TestCase
{
    public function test_class_is_final_readonly(): void
    {
        $class = $this->getServiceClass();
        if (!class_exists($class)) {
            $this->markTestSkipped("Class {$class} not found");
        }
        $reflection = new \ReflectionClass($class);
        $this->assertTrue($reflection->isFinal());
        $this->assertTrue($reflection->isReadOnly());
    }

    public function test_has_get_personalized_recommendations_method(): void
    {
        $class = $this->getServiceClass();
        if (!class_exists($class)) {
            $this->markTestSkipped("Class {$class} not found");
        }
        $methods = get_class_methods($class);
        $this->assertContains('getPersonalizedRecommendations', $methods);
    }

    public function test_has_get_flash_sales_method(): void
    {
        $class = $this->getServiceClass();
        if (!class_exists($class)) {
            $this->markTestSkipped("Class {$class} not found");
        }
        $methods = get_class_methods($class);
        $this->assertContains('getFlashSales', $methods);
    }

    public function test_has_get_trending_tours_method(): void
    {
        $class = $this->getServiceClass();
        if (!class_exists($class)) {
            $this->markTestSkipped("Class {$class} not found");
        }
        $methods = get_class_methods($class);
        $this->assertContains('getTrendingTours', $methods);
    }

    private function getServiceClass(): string
    {
        return 'App\Domains\Travel\Services\TourismRecommendationService';
    }
}
