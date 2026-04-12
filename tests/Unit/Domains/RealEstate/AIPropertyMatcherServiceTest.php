<?php declare(strict_types=1);

namespace Tests\Unit\Domains\RealEstate;

use PHPUnit\Framework\TestCase;

/**
 * Unit tests for AIPropertyMatcherService.
 *
 * @covers \App\Domains\RealEstate\Domain\Services\AIPropertyMatcherService
 */
final class AIPropertyMatcherServiceTest extends TestCase
{
    public function test_class_is_final(): void
    {
        $reflection = new \ReflectionClass(
            \App\Domains\RealEstate\Domain\Services\AIPropertyMatcherService::class
        );
        $this->assertTrue($reflection->isFinal(), 'AIPropertyMatcherService must be final');
    }

    public function test_class_is_readonly(): void
    {
        $reflection = new \ReflectionClass(
            \App\Domains\RealEstate\Domain\Services\AIPropertyMatcherService::class
        );
        $this->assertTrue($reflection->isReadOnly(), 'AIPropertyMatcherService must be readonly');
    }

    public function test_has_constructor_injection(): void
    {
        $reflection = new \ReflectionClass(
            \App\Domains\RealEstate\Domain\Services\AIPropertyMatcherService::class
        );
        $constructor = $reflection->getConstructor();
        $this->assertNotNull($constructor, 'AIPropertyMatcherService must have __construct');
        $this->assertGreaterThan(0, $constructor->getNumberOfParameters());
    }

    public function test_matchByDream_method_exists(): void
    {
        $this->assertTrue(
            method_exists(\App\Domains\RealEstate\Domain\Services\AIPropertyMatcherService::class, 'matchByDream'),
            'AIPropertyMatcherService must implement matchByDream()'
        );
    }

    public function test_calculateInvestmentPotential_method_exists(): void
    {
        $this->assertTrue(
            method_exists(\App\Domains\RealEstate\Domain\Services\AIPropertyMatcherService::class, 'calculateInvestmentPotential'),
            'AIPropertyMatcherService must implement calculateInvestmentPotential()'
        );
    }

}
