<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\HomeServices;

use PHPUnit\Framework\TestCase;
use App\Domains\HomeServices\Domain\Services\ContractorMatchingService;

/**
 * Unit tests for ContractorMatchingService.
 *
 * @covers \App\Domains\HomeServices\Domain\Services\ContractorMatchingService
 */
final class ContractorMatchingServiceTest extends TestCase
{
    public function test_class_is_final(): void
    {
        $reflection = new \ReflectionClass(
            ContractorMatchingService::class
        );
        $this->assertTrue($reflection->isFinal(), 'ContractorMatchingService must be final');
    }

    public function test_class_is_readonly(): void
    {
        $reflection = new \ReflectionClass(
            ContractorMatchingService::class
        );
        $this->assertTrue($reflection->isReadOnly(), 'ContractorMatchingService must be readonly');
    }

    public function test_has_constructor_injection(): void
    {
        $reflection = new \ReflectionClass(
            ContractorMatchingService::class
        );
        $constructor = $reflection->getConstructor();
        $this->assertNotNull($constructor, 'ContractorMatchingService must have __construct');
        $this->assertGreaterThan(0, $constructor->getNumberOfParameters());
    }

    public function test_find_contractors_method_exists(): void
    {
        $this->assertTrue(
            method_exists(ContractorMatchingService::class, 'findContractors'),
            'ContractorMatchingService must implement findContractors()'
        );
    }

    public function test___to_string_method_exists(): void
    {
        $this->assertTrue(
            method_exists(ContractorMatchingService::class, '__toString'),
            'ContractorMatchingService must implement __toString()'
        );
    }

    public function test_to_debug_array_method_exists(): void
    {
        $this->assertTrue(
            method_exists(ContractorMatchingService::class, 'toDebugArray'),
            'ContractorMatchingService must implement toDebugArray()'
        );
    }
}
