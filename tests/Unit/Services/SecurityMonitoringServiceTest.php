<?php declare(strict_types=1);

namespace Tests\Unit\Services;

use PHPUnit\Framework\TestCase;

/**
 * Unit tests for SecurityMonitoringService.
 *
 * @covers \App\Services\Security\SecurityMonitoringService
 */
final class SecurityMonitoringServiceTest extends TestCase
{
    public function test_class_is_final_readonly(): void
    {
        $reflection = new \ReflectionClass(\App\Services\Security\SecurityMonitoringService::class);
        $this->assertTrue($reflection->isFinal(), 'SecurityMonitoringService must be final');
        $this->assertTrue($reflection->isReadOnly(), 'SecurityMonitoringService must be readonly');
    }

    public function test_has_constructor_injection(): void
    {
        $reflection = new \ReflectionClass(\App\Services\Security\SecurityMonitoringService::class);
        $constructor = $reflection->getConstructor();
        $this->assertNotNull($constructor);
        $this->assertGreaterThan(0, $constructor->getNumberOfParameters());
    }

    public function test_logEvent_method_exists(): void
    {
        $this->assertTrue(
            method_exists(\App\Services\Security\SecurityMonitoringService::class, 'logEvent'),
            'SecurityMonitoringService must implement logEvent()'
        );
    }

    public function test_logFailedLogin_method_exists(): void
    {
        $this->assertTrue(
            method_exists(\App\Services\Security\SecurityMonitoringService::class, 'logFailedLogin'),
            'SecurityMonitoringService must implement logFailedLogin()'
        );
    }

    public function test_logRateLimitExceeded_method_exists(): void
    {
        $this->assertTrue(
            method_exists(\App\Services\Security\SecurityMonitoringService::class, 'logRateLimitExceeded'),
            'SecurityMonitoringService must implement logRateLimitExceeded()'
        );
    }

    public function test_logFraudAttempt_method_exists(): void
    {
        $this->assertTrue(
            method_exists(\App\Services\Security\SecurityMonitoringService::class, 'logFraudAttempt'),
            'SecurityMonitoringService must implement logFraudAttempt()'
        );
    }

}
