<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\RealEstate;

use PHPUnit\Framework\TestCase;
use App\Domains\RealEstate\Domain\Services\AIPropertyMatcherService;

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
            AIPropertyMatcherService::class
        );
        $this->assertTrue($reflection->isFinal(), 'AIPropertyMatcherService must be final');
    }

    public function test_class_is_readonly(): void
    {
        $reflection = new \ReflectionClass(
            AIPropertyMatcherService::class
        );
        $this->assertTrue($reflection->isReadOnly(), 'AIPropertyMatcherService must be readonly');
    }

    public function test_has_constructor_injection(): void
    {
        $reflection = new \ReflectionClass(
            AIPropertyMatcherService::class
        );
        $constructor = $reflection->getConstructor();
        $this->assertNotNull($constructor, 'AIPropertyMatcherService must have __construct');
        $this->assertGreaterThan(0, $constructor->getNumberOfParameters());
    }

    public function test_match_by_dream_method_exists(): void
    {
        $this->assertTrue(
            method_exists(AIPropertyMatcherService::class, 'matchByDream'),
            'AIPropertyMatcherService must implement matchByDream()'
        );
    }

    public function test_calculate_investment_potential_method_exists(): void
    {
        $this->assertTrue(
            method_exists(AIPropertyMatcherService::class, 'calculateInvestmentPotential'),
            'AIPropertyMatcherService must implement calculateInvestmentPotential()'
        );
    }
}
