<?php declare(strict_types=1);

namespace Tests\Unit\Services;

use PHPUnit\Framework\TestCase;

/**
 * Unit tests for BigDataAggregatorService.
 *
 * @covers \App\Services\ML\BigDataAggregatorService
 */
final class BigDataAggregatorServiceTest extends TestCase
{
    public function test_class_is_final_readonly(): void
    {
        $reflection = new \ReflectionClass(\App\Services\ML\BigDataAggregatorService::class);
        $this->assertTrue($reflection->isFinal(), 'BigDataAggregatorService must be final');
        $this->assertTrue($reflection->isReadOnly(), 'BigDataAggregatorService must be readonly');
    }

    public function test_has_constructor_injection(): void
    {
        $reflection = new \ReflectionClass(\App\Services\ML\BigDataAggregatorService::class);
        $constructor = $reflection->getConstructor();
        $this->assertNotNull($constructor);
        $this->assertGreaterThan(0, $constructor->getNumberOfParameters());
    }

    public function test_insertAnonymizedEvent_method_exists(): void
    {
        $this->assertTrue(
            method_exists(\App\Services\ML\BigDataAggregatorService::class, 'insertAnonymizedEvent'),
            'BigDataAggregatorService must implement insertAnonymizedEvent()'
        );
    }

    public function test_insertMarketingEvent_method_exists(): void
    {
        $this->assertTrue(
            method_exists(\App\Services\ML\BigDataAggregatorService::class, 'insertMarketingEvent'),
            'BigDataAggregatorService must implement insertMarketingEvent()'
        );
    }

    public function test_insertSecurityEvent_method_exists(): void
    {
        $this->assertTrue(
            method_exists(\App\Services\ML\BigDataAggregatorService::class, 'insertSecurityEvent'),
            'BigDataAggregatorService must implement insertSecurityEvent()'
        );
    }

    public function test_insertAuditLog_method_exists(): void
    {
        $this->assertTrue(
            method_exists(\App\Services\ML\BigDataAggregatorService::class, 'insertAuditLog'),
            'BigDataAggregatorService must implement insertAuditLog()'
        );
    }

    public function test_getGMV_method_exists(): void
    {
        $this->assertTrue(
            method_exists(\App\Services\ML\BigDataAggregatorService::class, 'getGMV'),
            'BigDataAggregatorService must implement getGMV()'
        );
    }

    public function test_getOrdersCount_method_exists(): void
    {
        $this->assertTrue(
            method_exists(\App\Services\ML\BigDataAggregatorService::class, 'getOrdersCount'),
            'BigDataAggregatorService must implement getOrdersCount()'
        );
    }

}
