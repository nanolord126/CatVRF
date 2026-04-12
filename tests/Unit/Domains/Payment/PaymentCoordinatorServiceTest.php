<?php declare(strict_types=1);

namespace Tests\Unit\Domains\Payment;

use PHPUnit\Framework\TestCase;

/**
 * Unit tests for PaymentCoordinatorService.
 *
 * @covers \App\Domains\Payment\Domain\Services\PaymentCoordinatorService
 */
final class PaymentCoordinatorServiceTest extends TestCase
{
    public function test_class_is_final(): void
    {
        $reflection = new \ReflectionClass(
            \App\Domains\Payment\Domain\Services\PaymentCoordinatorService::class
        );
        $this->assertTrue($reflection->isFinal(), 'PaymentCoordinatorService must be final');
    }

    public function test_class_is_readonly(): void
    {
        $reflection = new \ReflectionClass(
            \App\Domains\Payment\Domain\Services\PaymentCoordinatorService::class
        );
        $this->assertTrue($reflection->isReadOnly(), 'PaymentCoordinatorService must be readonly');
    }

    public function test_has_constructor_injection(): void
    {
        $reflection = new \ReflectionClass(
            \App\Domains\Payment\Domain\Services\PaymentCoordinatorService::class
        );
        $constructor = $reflection->getConstructor();
        $this->assertNotNull($constructor, 'PaymentCoordinatorService must have __construct');
        $this->assertGreaterThan(0, $constructor->getNumberOfParameters());
    }

    public function test_initPayment_method_exists(): void
    {
        $this->assertTrue(
            method_exists(\App\Domains\Payment\Domain\Services\PaymentCoordinatorService::class, 'initPayment'),
            'PaymentCoordinatorService must implement initPayment()'
        );
    }

    public function test_handleWebhook_method_exists(): void
    {
        $this->assertTrue(
            method_exists(\App\Domains\Payment\Domain\Services\PaymentCoordinatorService::class, 'handleWebhook'),
            'PaymentCoordinatorService must implement handleWebhook()'
        );
    }

    public function test_capture_method_exists(): void
    {
        $this->assertTrue(
            method_exists(\App\Domains\Payment\Domain\Services\PaymentCoordinatorService::class, 'capture'),
            'PaymentCoordinatorService must implement capture()'
        );
    }

    public function test_refund_method_exists(): void
    {
        $this->assertTrue(
            method_exists(\App\Domains\Payment\Domain\Services\PaymentCoordinatorService::class, 'refund'),
            'PaymentCoordinatorService must implement refund()'
        );
    }

}
