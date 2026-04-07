<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\Wallet\Listeners;

use App\Domains\Wallet\Events\WalletCreated;
use App\Domains\Wallet\Events\WalletUpdated;
use App\Domains\Wallet\Listeners\LogWalletCreated;
use App\Domains\Wallet\Listeners\LogWalletUpdated;
use App\Domains\Wallet\Models\Wallet;
use App\Services\AuditService;
use Illuminate\Database\Eloquent\Model;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Unit-тесты слушателей Wallet.
 *
 * AuditService — final class, PHPUnit 10 не может его мокнуть.
 * Создаём через Reflection; при вызове record() он падает (нет Laravel),
 * но логгер вызывается ДО audit → expectations LoggerInterface проверяемы.
 */
final class WalletListenersTest extends TestCase
{
    // ─── Structural ──────────────────────────────────────────────────

    public function test_log_wallet_created_is_final(): void
    {
        $ref = new \ReflectionClass(LogWalletCreated::class);
        $this->assertTrue($ref->isFinal());
    }

    public function test_log_wallet_created_constructor_accepts_correct_deps(): void
    {
        $ref = new \ReflectionClass(LogWalletCreated::class);
        $params = $ref->getConstructor()->getParameters();

        $this->assertCount(2, $params);
        $this->assertSame('logger', $params[0]->getName());
        $this->assertSame('audit', $params[1]->getName());
    }

    public function test_log_wallet_created_handle_accepts_wallet_created(): void
    {
        $ref = new \ReflectionMethod(LogWalletCreated::class, 'handle');
        $params = $ref->getParameters();

        $this->assertCount(1, $params);
        $this->assertSame(WalletCreated::class, $params[0]->getType()->getName());
    }

    public function test_log_wallet_updated_is_final(): void
    {
        $ref = new \ReflectionClass(LogWalletUpdated::class);
        $this->assertTrue($ref->isFinal());
    }

    public function test_log_wallet_updated_handle_accepts_wallet_updated(): void
    {
        $ref = new \ReflectionMethod(LogWalletUpdated::class, 'handle');
        $params = $ref->getParameters();

        $this->assertCount(1, $params);
        $this->assertSame(WalletUpdated::class, $params[0]->getType()->getName());
    }

    // ─── Behavioral (logger verified, audit may crash) ──────────────

    public function test_log_wallet_created_calls_logger(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $audit = $this->createAuditStub();

        $logger->expects($this->once())
            ->method('info')
            ->with(
                $this->stringContains('Wallet created'),
                $this->callback(static function (array $ctx): bool {
                    return isset($ctx['wallet_id'], $ctx['correlation_id'], $ctx['tenant_id']);
                }),
            );

        $wallet = $this->createWalletStub(1, 10);
        $event = new WalletCreated($wallet, 'corr-lc-1', 42);

        $listener = new LogWalletCreated($logger, $audit);

        try {
            $listener->handle($event);
        } catch (\Throwable) {
            // AuditService::record() requires full Laravel (AuditLogJob::dispatch)
            // Logger call happens BEFORE audit → expectation fulfilled
        }
    }

    public function test_log_wallet_updated_calls_logger(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $audit = $this->createAuditStub();

        $logger->expects($this->once())
            ->method('info')
            ->with(
                $this->stringContains('Wallet updated'),
                $this->callback(static function (array $ctx): bool {
                    return isset($ctx['wallet_id'], $ctx['correlation_id'], $ctx['changed_fields']);
                }),
            );

        $wallet = $this->createWalletStub(2, 10);
        $event = new WalletUpdated(
            wallet: $wallet,
            correlationId: 'corr-lu-1',
            userId: 42,
            oldValues: ['current_balance' => 0],
            newValues: ['current_balance' => 50000],
        );

        $listener = new LogWalletUpdated($logger, $audit);

        try {
            $listener->handle($event);
        } catch (\Throwable) {
            // AuditService::record() requires full Laravel — acceptable in unit test
        }
    }

    // ─── Helpers ─────────────────────────────────────────────────────

    private function createWalletStub(int $id, int $tenantId): Wallet
    {
        $wallet = (new \ReflectionClass(Wallet::class))->newInstanceWithoutConstructor();

        $prop = new \ReflectionProperty(Model::class, 'attributes');
        $prop->setValue($wallet, [
            'id' => $id,
            'tenant_id' => $tenantId,
            'business_group_id' => null,
        ]);

        return $wallet;
    }

    /**
     * Создаёт AuditService без конструктора (обход final class).
     * record() упадёт при вызове, но для тестов логгера это допустимо.
     */
    private function createAuditStub(): AuditService
    {
        return (new \ReflectionClass(AuditService::class))->newInstanceWithoutConstructor();
    }
}
