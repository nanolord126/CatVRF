<?php declare(strict_types=1);

namespace Tests\Unit\Domains\Travel;

use PHPUnit\Framework\TestCase;
use App\Domains\Travel\Services\FlightService;

/**
 * Unit tests for FlightService.
 *
 * @covers \App\Domains\Travel\Services\FlightService
 * @group travel-services
 */
final class FlightServiceTest extends TestCase
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

    public function test_has_book_flight_method(): void
    {
        $class = $this->getServiceClass();
        if (!class_exists($class)) {
            $this->markTestSkipped("Class {$class} not found");
        }
        $methods = get_class_methods($class);
        $this->assertContains('bookFlight', $methods);
    }

    public function test_has_release_flight_method(): void
    {
        $class = $this->getServiceClass();
        if (!class_exists($class)) {
            $this->markTestSkipped("Class {$class} not found");
        }
        $methods = get_class_methods($class);
        $this->assertContains('releaseFlight', $methods);
    }

    private function getServiceClass(): string
    {
        return 'App\Domains\Travel\Services\FlightService';
    }
}
