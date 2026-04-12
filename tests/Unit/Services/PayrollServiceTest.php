<?php declare(strict_types=1);

namespace Tests\Unit\Services;

use PHPUnit\Framework\TestCase;

/**
 * Unit tests for PayrollService.
 *
 * @covers \App\Services\HR\PayrollService
 */
final class PayrollServiceTest extends TestCase
{
    public function test_class_is_final_readonly(): void
    {
        $reflection = new \ReflectionClass(\App\Services\HR\PayrollService::class);
        $this->assertTrue($reflection->isFinal(), 'PayrollService must be final');
        $this->assertTrue($reflection->isReadOnly(), 'PayrollService must be readonly');
    }

    public function test_has_constructor_injection(): void
    {
        $reflection = new \ReflectionClass(\App\Services\HR\PayrollService::class);
        $constructor = $reflection->getConstructor();
        $this->assertNotNull($constructor);
        $this->assertGreaterThan(0, $constructor->getNumberOfParameters());
    }

    public function test_calculate_method_exists(): void
    {
        $this->assertTrue(
            method_exists(\App\Services\HR\PayrollService::class, 'calculate'),
            'PayrollService must implement calculate()'
        );
    }

    public function test_approve_method_exists(): void
    {
        $this->assertTrue(
            method_exists(\App\Services\HR\PayrollService::class, 'approve'),
            'PayrollService must implement approve()'
        );
    }

    public function test_pay_method_exists(): void
    {
        $this->assertTrue(
            method_exists(\App\Services\HR\PayrollService::class, 'pay'),
            'PayrollService must implement pay()'
        );
    }

    public function test_payAll_method_exists(): void
    {
        $this->assertTrue(
            method_exists(\App\Services\HR\PayrollService::class, 'payAll'),
            'PayrollService must implement payAll()'
        );
    }

}
