<?php declare(strict_types=1);

namespace Tests\Unit\Domains\Finances;

use PHPUnit\Framework\TestCase;

/**
 * Unit tests for PaymentDistributionService.
 *
 * @covers \App\Domains\Finances\Domain\Services\PaymentDistributionService
 */
final class PaymentDistributionServiceTest extends TestCase
{
    public function test_class_is_final(): void
    {
        $reflection = new \ReflectionClass(
            \App\Domains\Finances\Domain\Services\PaymentDistributionService::class
        );
        $this->assertTrue($reflection->isFinal(), 'PaymentDistributionService must be final');
    }

    public function test_class_is_readonly(): void
    {
        $reflection = new \ReflectionClass(
            \App\Domains\Finances\Domain\Services\PaymentDistributionService::class
        );
        $this->assertTrue($reflection->isReadOnly(), 'PaymentDistributionService must be readonly');
    }

    public function test_has_constructor_injection(): void
    {
        $reflection = new \ReflectionClass(
            \App\Domains\Finances\Domain\Services\PaymentDistributionService::class
        );
        $constructor = $reflection->getConstructor();
        $this->assertNotNull($constructor, 'PaymentDistributionService must have __construct');
        $this->assertGreaterThan(0, $constructor->getNumberOfParameters());
    }

    public function test_distributePayment_method_exists(): void
    {
        $this->assertTrue(
            method_exists(\App\Domains\Finances\Domain\Services\PaymentDistributionService::class, 'distributePayment'),
            'PaymentDistributionService must implement distributePayment()'
        );
    }

}
