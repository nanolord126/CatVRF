<?php declare(strict_types=1);

namespace Tests\Unit\Domains\Travel;

use PHPUnit\Framework\TestCase;
use App\Domains\Travel\Services\TourismVirtualTourService;

/**
 * Unit tests for TourismVirtualTourService.
 *
 * @covers \App\Domains\Travel\Services\TourismVirtualTourService
 * @group travel-services
 */
final class TourismVirtualTourServiceTest extends TestCase
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

    public function test_has_get_virtual_tour_url_method(): void
    {
        $class = $this->getServiceClass();
        if (!class_exists($class)) {
            $this->markTestSkipped("Class {$class} not found");
        }
        $methods = get_class_methods($class);
        $this->assertContains('getVirtualTourUrl', $methods);
    }

    public function test_has_is_ar_available_method(): void
    {
        $class = $this->getServiceClass();
        if (!class_exists($class)) {
            $this->markTestSkipped("Class {$class} not found");
        }
        $methods = get_class_methods($class);
        $this->assertContains('isARAvailable', $methods);
    }

    public function test_has_get_ar_model_url_method(): void
    {
        $class = $this->getServiceClass();
        if (!class_exists($class)) {
            $this->markTestSkipped("Class {$class} not found");
        }
        $methods = get_class_methods($class);
        $this->assertContains('getARModelUrl', $methods);
    }

    public function test_has_track_virtual_tour_view_method(): void
    {
        $class = $this->getServiceClass();
        if (!class_exists($class)) {
            $this->markTestSkipped("Class {$class} not found");
        }
        $methods = get_class_methods($class);
        $this->assertContains('trackVirtualTourView', $methods);
    }

    private function getServiceClass(): string
    {
        return 'App\Domains\Travel\Services\TourismVirtualTourService';
    }
}
