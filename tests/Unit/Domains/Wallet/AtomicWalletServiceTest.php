<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\Wallet;

use App\Domains\Wallet\Enums\BalanceTransactionType;
use App\Domains\Wallet\Models\Wallet;
use App\Domains\Wallet\Services\AtomicWalletService;
use App\Models\BalanceTransaction;
use App\Services\AuditService;
use App\Services\FraudControlService;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Contracts\Redis\Factory as RedisFactory;
use Illuminate\Database\DatabaseManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Redis;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * AtomicWalletService Unit Tests
 *
 * Tests atomic wallet operations with Redis Lua scripts.
 */
final class AtomicWalletServiceTest extends TestCase
{
    use RefreshDatabase;

    private AtomicWalletService $walletService;
    private Wallet $wallet;

    protected function setUp(): void
    {
        parent::setUp();

        $db = app(DatabaseManager::class);
        $logger = app(\Psr\Log\LoggerInterface::class);
        $guard = app(Guard::class);
        $fraud = $this->createMock(FraudControlService::class);
        $audit = $this->createMock(AuditService::class);
        $redis = app(RedisFactory::class);

        $fraud->method('check')->willReturn(['decision' => 'allow', 'score' => 0.0]);
        $audit->method('log');

        $this->walletService = new AtomicWalletService(
            $db,
            $logger,
            $guard,
            $fraud,
            $audit,
            $redis,
        );

        // Create test wallet
        $this->wallet = Wallet::factory()->create([
            'current_balance' => 100000, // 1000 RUB in kopecks
            'hold_amount' => 0,
        ]);

        // Clear Redis
        Redis::connection()->flushdb();
    }

    protected function tearDown(): void
    {
        Redis::connection()->flushdb();
        parent::tearDown();
    }

    #[Test]
    public function it_credits_wallet(): void
    {
        $initialBalance = $this->wallet->current_balance;
        $amount = 50000; // 500 RUB

        $wallet = $this->walletService->credit(
            walletId: $this->wallet->id,
            amount: $amount,
            type: BalanceTransactionType::DEPOSIT,
            correlationId: 'test-correlation-1',
        );

        $this->assertEquals($initialBalance + $amount, $wallet->current_balance);
        $this->assertDatabaseHas('balance_transactions', [
            'wallet_id' => $this->wallet->id,
            'type' => BalanceTransactionType::DEPOSIT->value,
            'amount' => $amount,
        ]);
    }

    #[Test]
    public function it_debits_wallet_with_sufficient_balance(): void
    {
        $initialBalance = $this->wallet->current_balance;
        $amount = 50000; // 500 RUB

        $wallet = $this->walletService->debit(
            walletId: $this->wallet->id,
            amount: $amount,
            type: BalanceTransactionType::WITHDRAWAL,
            correlationId: 'test-correlation-2',
        );

        $this->assertEquals($initialBalance - $amount, $wallet->current_balance);
        $this->assertDatabaseHas('balance_transactions', [
            'wallet_id' => $this->wallet->id,
            'type' => BalanceTransactionType::WITHDRAWAL->value,
            'amount' => $amount,
        ]);
    }

    #[Test]
    public function it_throws_exception_on_insufficient_balance(): void
    {
        $amount = 200000; // 2000 RUB (more than balance)

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Insufficient balance');

        $this->walletService->debit(
            walletId: $this->wallet->id,
            amount: $amount,
            type: BalanceTransactionType::WITHDRAWAL,
            correlationId: 'test-correlation-3',
        );
    }

    #[Test]
    public function it_holds_amount(): void
    {
        $initialBalance = $this->wallet->current_balance;
        $initialHold = $this->wallet->hold_amount;
        $amount = 30000; // 300 RUB

        $wallet = $this->walletService->hold(
            walletId: $this->wallet->id,
            amount: $amount,
            correlationId: 'test-correlation-4',
        );

        $this->assertEquals($initialBalance - $amount, $wallet->current_balance);
        $this->assertEquals($initialHold + $amount, $wallet->hold_amount);
        $this->assertDatabaseHas('balance_transactions', [
            'wallet_id' => $this->wallet->id,
            'type' => BalanceTransactionType::HOLD->value,
            'amount' => $amount,
        ]);
    }

    #[Test]
    public function it_releases_hold(): void
    {
        // First hold
        $this->walletService->hold(
            walletId: $this->wallet->id,
            amount: 30000,
            correlationId: 'test-correlation-5',
        );

        $initialBalance = $this->wallet->current_balance;
        $initialHold = $this->wallet->hold_amount;

        // Release hold
        $wallet = $this->walletService->credit(
            walletId: $this->wallet->id,
            amount: 30000,
            type: BalanceTransactionType::RELEASE_HOLD,
            correlationId: 'test-correlation-6',
        );

        $this->assertEquals($initialBalance + 30000, $wallet->current_balance);
        $this->assertEquals($initialHold - 30000, $wallet->hold_amount);
    }

    #[Test]
    public function it_throws_exception_on_insufficient_hold_for_release(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Not enough hold amount');

        $this->walletService->credit(
            walletId: $this->wallet->id,
            amount: 50000,
            type: BalanceTransactionType::RELEASE_HOLD,
            correlationId: 'test-correlation-7',
        );
    }

    #[Test]
    public function it_throws_exception_on_insufficient_balance_for_hold(): void
    {
        $amount = 200000; // More than balance

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Insufficient balance for hold');

        $this->walletService->hold(
            walletId: $this->wallet->id,
            amount: $amount,
            correlationId: 'test-correlation-8',
        );
    }

    #[Test]
    public function it_gets_cached_balance(): void
    {
        // Sync cache first
        $this->walletService->syncCache($this->wallet->id);

        $cachedBalance = $this->walletService->getCachedBalance($this->wallet->id);

        $this->assertEquals($this->wallet->current_balance, $cachedBalance);
    }

    #[Test]
    public function it_returns_null_for_non_cached_balance(): void
    {
        $cachedBalance = $this->walletService->getCachedBalance($this->wallet->id);

        $this->assertNull($cachedBalance);
    }

    #[Test]
    public function it_syncs_cache_with_database(): void
    {
        // Update database balance
        $this->wallet->update(['current_balance' => 150000]);
        $this->wallet->refresh();

        // Sync cache
        $this->walletService->syncCache($this->wallet->id);

        // Check cached balance
        $cachedBalance = $this->walletService->getCachedBalance($this->wallet->id);

        $this->assertEquals(150000, $cachedBalance);
    }

    #[Test]
    public function it_validates_positive_amount(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Amount must be positive');

        $this->walletService->credit(
            walletId: $this->wallet->id,
            amount: -100,
            type: BalanceTransactionType::DEPOSIT,
            correlationId: 'test-correlation-9',
        );
    }

    #[Test]
    public function it_validates_credit_type(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid credit type');

        $this->walletService->credit(
            walletId: $this->wallet->id,
            amount: 10000,
            type: BalanceTransactionType::WITHDRAWAL, // Invalid for credit
            correlationId: 'test-correlation-10',
        );
    }

    #[Test]
    public function it_validates_debit_type(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid debit type');

        $this->walletService->debit(
            walletId: $this->wallet->id,
            amount: 10000,
            type: BalanceTransactionType::DEPOSIT, // Invalid for debit
            correlationId: 'test-correlation-11',
        );
    }
}
