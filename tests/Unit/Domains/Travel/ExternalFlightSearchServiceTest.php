<?php declare(strict_types=1);

namespace Tests\Unit\Domains\Travel;

use PHPUnit\Framework\TestCase;
use App\Domains\Travel\Services\ExternalFlightSearchService;

/**
 * Unit tests for ExternalFlightSearchService.
 *
 * @covers \App\Domains\Travel\Services\ExternalFlightSearchService
 * @group travel-services
 * @group external-apis
 */
final class ExternalFlightSearchServiceTest extends TestCase
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

    public function test_has_search_flights_method(): void
    {
        $class = $this->getServiceClass();
        if (!class_exists($class)) {
            $this->markTestSkipped("Class {$class} not found");
        }
        $methods = get_class_methods($class);
        $this->assertContains('searchFlights', $methods);
    }

    public function test_has_search_amadeus_method(): void
    {
        $class = $this->getServiceClass();
        if (!class_exists($class)) {
            $this->markTestSkipped("Class {$class} not found");
        }
        $methods = get_class_methods($class);
        $this->assertContains('searchAmadeus', $methods);
    }

    public function test_has_search_sabre_method(): void
    {
        $class = $this->getServiceClass();
        if (!class_exists($class)) {
            $this->markTestSkipped("Class {$class} not found");
        }
        $methods = get_class_methods($class);
        $this->assertContains('searchSabre', $methods);
    }

    public function test_has_search_skyscanner_method(): void
    {
        $class = $this->getServiceClass();
        if (!class_exists($class)) {
            $this->markTestSkipped("Class {$class} not found");
        }
        $methods = get_class_methods($class);
        $this->assertContains('searchSkyscanner', $methods);
    }

    public function test_has_get_amadeus_token_method(): void
    {
        $class = $this->getServiceClass();
        if (!class_exists($class)) {
            $this->markTestSkipped("Class {$class} not found");
        }
        $methods = get_class_methods($class);
        $this->assertContains('getAmadeusToken', $methods);
    }

    public function test_has_get_sabre_token_method(): void
    {
        $class = $this->getServiceClass();
        if (!class_exists($class)) {
            $this->markTestSkipped("Class {$class} not found");
        }
        $methods = get_class_methods($class);
        $this->assertContains('getSabreToken', $methods);
    }

    public function test_has_normalize_amadeus_results_method(): void
    {
        $class = $this->getServiceClass();
        if (!class_exists($class)) {
            $this->markTestSkipped("Class {$class} not found");
        }
        $methods = get_class_methods($class);
        $this->assertContains('normalizeAmadeusResults', $methods);
    }

    public function test_has_normalize_sabre_results_method(): void
    {
        $class = $this->getServiceClass();
        if (!class_exists($class)) {
            $this->markTestSkipped("Class {$class} not found");
        }
        $methods = get_class_methods($class);
        $this->assertContains('normalizeSabreResults', $methods);
    }

    public function test_has_normalize_skyscanner_results_method(): void
    {
        $class = $this->getServiceClass();
        if (!class_exists($class)) {
            $this->markTestSkipped("Class {$class} not found");
        }
        $methods = get_class_methods($class);
        $this->assertContains('normalizeSkyscannerResults', $methods);
    }

    public function test_has_get_fallback_results_method(): void
    {
        $class = $this->getServiceClass();
        if (!class_exists($class)) {
            $this->markTestSkipped("Class {$class} not found");
        }
        $methods = get_class_methods($class);
        $this->assertContains('getFallbackResults', $methods);
    }

    public function test_has_get_cache_key_method(): void
    {
        $class = $this->getServiceClass();
        if (!class_exists($class)) {
            $this->markTestSkipped("Class {$class} not found");
        }
        $methods = get_class_methods($class);
        $this->assertContains('getCacheKey', $methods);
    }

    private function getServiceClass(): string
    {
        return 'App\\Domains\\Travel\\Services\\ExternalFlightSearchService';
    }
}
