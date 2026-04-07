<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\Wallet\Services;

use App\Domains\Wallet\Enums\BalanceTransactionType;
use App\Domains\Wallet\Services\WalletService;
use App\Services\AuditService;
use App\Services\FraudControlService;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Database\DatabaseManager;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Unit-тесты WalletService.
 *
 * FraudControlService и AuditService — final classes, PHPUnit 10 не может их мокнуть.
 * Создаём их через newInstanceWithoutConstructor() (Reflection).
 * Тестируемые private-методы (guard*, getCurrentUserId) не используют fraud/audit.
 */
final class WalletServiceTest extends TestCase
{
    public function test_can_instantiate_wallet_service(): void
    {
        $service = $this->createWalletService();
        $this->assertInstanceOf(WalletService::class, $service);
    }

    public function test_guard_amount_rejects_zero(): void
    {
        $service = $this->createWalletService();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Amount must be positive');

        $method = new \ReflectionMethod(WalletService::class, 'guardAmount');
        $method->setAccessible(true);
        $method->invoke($service, 0);
    }

    public function test_guard_amount_rejects_negative(): void
    {
        $service = $this->createWalletService();

        $this->expectException(\InvalidArgumentException::class);

        $method = new \ReflectionMethod(WalletService::class, 'guardAmount');
        $method->setAccessible(true);
        $method->invoke($service, -100);
    }

    public function test_guard_credit_type_rejects_debit_type(): void
    {
        $service = $this->createWalletService();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid credit type');

        $method = new \ReflectionMethod(WalletService::class, 'guardCreditType');
        $method->setAccessible(true);
        $method->invoke($service, BalanceTransactionType::WITHDRAWAL);
    }

    public function test_guard_credit_type_accepts_deposit(): void
    {
        $service = $this->createWalletService();

        $method = new \ReflectionMethod(WalletService::class, 'guardCreditType');
        $method->setAccessible(true);

        $method->invoke($service, BalanceTransactionType::DEPOSIT);
        $this->assertTrue(true);
    }

    public function test_guard_debit_type_rejects_credit_type(): void
    {
        $service = $this->createWalletService();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid debit type');

        $method = new \ReflectionMethod(WalletService::class, 'guardDebitType');
        $method->setAccessible(true);
        $method->invoke($service, BalanceTransactionType::DEPOSIT);
    }

    public function test_guard_debit_type_accepts_withdrawal(): void
    {
        $service = $this->createWalletService();

        $method = new \ReflectionMethod(WalletService::class, 'guardDebitType');
        $method->setAccessible(true);

        $method->invoke($service, BalanceTransactionType::WITHDRAWAL);
        $this->assertTrue(true);
    }

    public function test_guard_debit_type_accepts_hold(): void
    {
        $service = $this->createWalletService();

        $method = new \ReflectionMethod(WalletService::class, 'guardDebitType');
        $method->setAccessible(true);

        $method->invoke($service, BalanceTransactionType::HOLD);
        $this->assertTrue(true);
    }

    public function test_guard_credit_type_accepts_release_hold(): void
    {
        $service = $this->createWalletService();

        $method = new \ReflectionMethod(WalletService::class, 'guardCreditType');
        $method->setAccessible(true);

        $method->invoke($service, BalanceTransactionType::RELEASE_HOLD);
        $this->assertTrue(true);
    }

    public function test_get_current_user_id_returns_null_when_no_user(): void
    {
        $guard = $this->createMock(Guard::class);
        $guard->method('user')->willReturn(null);

        $service = new WalletService(
            $this->createMock(DatabaseManager::class),
            $this->createMock(LoggerInterface::class),
            $guard,
            $this->createFraudStub(),
            $this->createAuditStub(),
            $this->createMock(CacheRepository::class),
        );

        $method = new \ReflectionMethod(WalletService::class, 'getCurrentUserId');
        $method->setAccessible(true);

        $this->assertNull($method->invoke($service));
    }

    // ─── Helpers ─────────────────────────────────────────────────────

    private function createWalletService(): WalletService
    {
        return new WalletService(
            $this->createMock(DatabaseManager::class),
            $this->createMock(LoggerInterface::class),
            $this->createMock(Guard::class),
            $this->createFraudStub(),
            $this->createAuditStub(),
            $this->createMock(CacheRepository::class),
        );
    }

    /** FraudControlService — final class, stub через Reflection. */
    private function createFraudStub(): FraudControlService
    {
        return (new \ReflectionClass(FraudControlService::class))->newInstanceWithoutConstructor();
    }

    /** AuditService — final class, stub через Reflection. */
    private function createAuditStub(): AuditService
    {
        return (new \ReflectionClass(AuditService::class))->newInstanceWithoutConstructor();
    }
}
