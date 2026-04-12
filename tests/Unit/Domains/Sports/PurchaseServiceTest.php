<?php declare(strict_types=1);

namespace Tests\Unit\Domains\Sports;

use PHPUnit\Framework\TestCase;

/**
 * Unit tests for PurchaseService.
 *
 * @covers \App\Domains\Sports\Domain\Services\PurchaseService
 */
final class PurchaseServiceTest extends TestCase
{
    public function test_class_is_final(): void
    {
        $reflection = new \ReflectionClass(
            \App\Domains\Sports\Domain\Services\PurchaseService::class
        );
        $this->assertTrue($reflection->isFinal(), 'PurchaseService must be final');
    }

    public function test_class_is_readonly(): void
    {
        $reflection = new \ReflectionClass(
            \App\Domains\Sports\Domain\Services\PurchaseService::class
        );
        $this->assertTrue($reflection->isReadOnly(), 'PurchaseService must be readonly');
    }

    public function test_has_constructor_injection(): void
    {
        $reflection = new \ReflectionClass(
            \App\Domains\Sports\Domain\Services\PurchaseService::class
        );
        $constructor = $reflection->getConstructor();
        $this->assertNotNull($constructor, 'PurchaseService must have __construct');
        $this->assertGreaterThan(0, $constructor->getNumberOfParameters());
    }

    public function test_createPurchase_method_exists(): void
    {
        $this->assertTrue(
            method_exists(\App\Domains\Sports\Domain\Services\PurchaseService::class, 'createPurchase'),
            'PurchaseService must implement createPurchase()'
        );
    }

    public function test_confirmPayment_method_exists(): void
    {
        $this->assertTrue(
            method_exists(\App\Domains\Sports\Domain\Services\PurchaseService::class, 'confirmPayment'),
            'PurchaseService must implement confirmPayment()'
        );
    }

    public function test_refundPurchase_method_exists(): void
    {
        $this->assertTrue(
            method_exists(\App\Domains\Sports\Domain\Services\PurchaseService::class, 'refundPurchase'),
            'PurchaseService must implement refundPurchase()'
        );
    }

}
