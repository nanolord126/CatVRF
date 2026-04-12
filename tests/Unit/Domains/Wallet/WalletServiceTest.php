<?php declare(strict_types=1);

namespace Tests\Unit\Domains\Wallet;

use PHPUnit\Framework\TestCase;

/**
 * Unit tests for WalletService.
 *
 * @covers \App\Domains\Wallet\Domain\Services\WalletService
 */
final class WalletServiceTest extends TestCase
{
    public function test_class_is_final(): void
    {
        $reflection = new \ReflectionClass(
            \App\Domains\Wallet\Domain\Services\WalletService::class
        );
        $this->assertTrue($reflection->isFinal(), 'WalletService must be final');
    }

    public function test_class_is_readonly(): void
    {
        $reflection = new \ReflectionClass(
            \App\Domains\Wallet\Domain\Services\WalletService::class
        );
        $this->assertTrue($reflection->isReadOnly(), 'WalletService must be readonly');
    }

    public function test_has_constructor_injection(): void
    {
        $reflection = new \ReflectionClass(
            \App\Domains\Wallet\Domain\Services\WalletService::class
        );
        $constructor = $reflection->getConstructor();
        $this->assertNotNull($constructor, 'WalletService must have __construct');
        $this->assertGreaterThan(0, $constructor->getNumberOfParameters());
    }

    public function test_credit_method_exists(): void
    {
        $this->assertTrue(
            method_exists(\App\Domains\Wallet\Domain\Services\WalletService::class, 'credit'),
            'WalletService must implement credit()'
        );
    }

    public function test_debit_method_exists(): void
    {
        $this->assertTrue(
            method_exists(\App\Domains\Wallet\Domain\Services\WalletService::class, 'debit'),
            'WalletService must implement debit()'
        );
    }

    public function test_hold_method_exists(): void
    {
        $this->assertTrue(
            method_exists(\App\Domains\Wallet\Domain\Services\WalletService::class, 'hold'),
            'WalletService must implement hold()'
        );
    }

}
