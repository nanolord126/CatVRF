<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use PHPUnit\Framework\TestCase;
use App\Services\ML\BigDataAggregatorService;

/**
 * Unit tests for BigDataAggregatorService.
 *
 * @covers \App\Services\ML\BigDataAggregatorService
 */
final class BigDataAggregatorServiceTest extends TestCase
{
    public function test_class_is_final_readonly(): void
    {
        $reflection = new \ReflectionClass(BigDataAggregatorService::class);
        $this->assertTrue($reflection->isFinal(), 'BigDataAggregatorService must be final');
        $this->assertTrue($reflection->isReadOnly(), 'BigDataAggregatorService must be readonly');
    }

    public function test_has_constructor_injection(): void
    {
        $reflection = new \ReflectionClass(BigDataAggregatorService::class);
        $constructor = $reflection->getConstructor();
        $this->assertNotNull($constructor);
        $this->assertGreaterThan(0, $constructor->getNumberOfParameters());
    }

    public function test_insert_anonymized_event_method_exists(): void
    {
        $this->assertTrue(
            method_exists(BigDataAggregatorService::class, 'insertAnonymizedEvent'),
            'BigDataAggregatorService must implement insertAnonymizedEvent()'
        );
    }

    public function test_insert_marketing_event_method_exists(): void
    {
        $this->assertTrue(
            method_exists(BigDataAggregatorService::class, 'insertMarketingEvent'),
            'BigDataAggregatorService must implement insertMarketingEvent()'
        );
    }

    public function test_insert_security_event_method_exists(): void
    {
        $this->assertTrue(
            method_exists(BigDataAggregatorService::class, 'insertSecurityEvent'),
            'BigDataAggregatorService must implement insertSecurityEvent()'
        );
    }

    public function test_insert_audit_log_method_exists(): void
    {
        $this->assertTrue(
            method_exists(BigDataAggregatorService::class, 'insertAuditLog'),
            'BigDataAggregatorService must implement insertAuditLog()'
        );
    }

    public function test_get_gm_v_method_exists(): void
    {
        $this->assertTrue(
            method_exists(BigDataAggregatorService::class, 'getGMV'),
            'BigDataAggregatorService must implement getGMV()'
        );
    }

    public function test_get_orders_count_method_exists(): void
    {
        $this->assertTrue(
            method_exists(BigDataAggregatorService::class, 'getOrdersCount'),
            'BigDataAggregatorService must implement getOrdersCount()'
        );
    }
}
