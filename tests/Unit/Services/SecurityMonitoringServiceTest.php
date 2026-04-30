<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use PHPUnit\Framework\TestCase;
use App\Services\Security\SecurityMonitoringService;

/**
 * Unit tests for SecurityMonitoringService.
 *
 * @covers \App\Services\Security\SecurityMonitoringService
 */
final class SecurityMonitoringServiceTest extends TestCase
{
    public function test_class_is_final_readonly(): void
    {
        $reflection = new \ReflectionClass(SecurityMonitoringService::class);
        $this->assertTrue($reflection->isFinal(), 'SecurityMonitoringService must be final');
        $this->assertTrue($reflection->isReadOnly(), 'SecurityMonitoringService must be readonly');
    }

    public function test_has_constructor_injection(): void
    {
        $reflection = new \ReflectionClass(SecurityMonitoringService::class);
        $constructor = $reflection->getConstructor();
        $this->assertNotNull($constructor);
        $this->assertGreaterThan(0, $constructor->getNumberOfParameters());
    }

    public function test_log_event_method_exists(): void
    {
        $this->assertTrue(
            method_exists(SecurityMonitoringService::class, 'logEvent'),
            'SecurityMonitoringService must implement logEvent()'
        );
    }

    public function test_log_failed_login_method_exists(): void
    {
        $this->assertTrue(
            method_exists(SecurityMonitoringService::class, 'logFailedLogin'),
            'SecurityMonitoringService must implement logFailedLogin()'
        );
    }

    public function test_log_rate_limit_exceeded_method_exists(): void
    {
        $this->assertTrue(
            method_exists(SecurityMonitoringService::class, 'logRateLimitExceeded'),
            'SecurityMonitoringService must implement logRateLimitExceeded()'
        );
    }

    public function test_log_fraud_attempt_method_exists(): void
    {
        $this->assertTrue(
            method_exists(SecurityMonitoringService::class, 'logFraudAttempt'),
            'SecurityMonitoringService must implement logFraudAttempt()'
        );
    }
}
