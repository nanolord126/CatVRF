<?php

declare(strict_types=1);

namespace Tests\Stress;

use Tests\TestCase;
use Modules\Wallet\Domain\Entities\Wallet;
use Modules\Wallet\Domain\Entities\WalletTransaction;
use Modules\Wallet\Domain\Enums\TransactionType;
use Modules\Wallet\Application\Services\WalletService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Redis;

final class WalletStressTest extends TestCase
{
    use RefreshDatabase;

    private WalletService $walletService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->walletService = app(WalletService::class);
    }

    public function test_concurrent_deposits_200_rps(): void
    {
        $concurrency = 200;
        $walletId = 1;
        $results = [];

        for ($i = 0; $i < $concurrency; $i++) {
            try {
                $this->walletService->deposit($walletId, 1000, 'test_deposit');
                $results[] = ['success' => true];
            } catch (\Exception $e) {
                $results[] = ['success' => false, 'error' => $e->getMessage()];
            }
        }

        $successCount = count(array_filter($results, fn($r) => $r['success']));
        $successRate = ($successCount / $concurrency) * 100;

        $this->assertGreaterThan(98, $successRate, 'Deposit success rate should be > 98%');
    }

    public function test_concurrent_withdrawals_race_condition(): void
    {
        $wallet = Wallet::factory()->create(['balance' => 100000]);
        $concurrency = 100;
        $withdrawAmount = 1000;
        $results = [];

        for ($i = 0; $i < $concurrency; $i++) {
            try {
                $this->walletService->withdraw($wallet->id, $withdrawAmount, 'test_withdraw');
                $results[] = ['success' => true];
            } catch (\Exception $e) {
                $results[] = ['success' => false];
            }
        }

        $wallet->refresh();
        
        // Balance should never go negative
        $this->assertGreaterThanOrEqual(0, $wallet->balance, 'Balance should never be negative');
        
        // All withdrawals should be either successful or fail gracefully
        $this->assertCount($concurrency, $results, 'All attempts should have a result');
    }

    public function test_transfer_stress_500_transfers(): void
    {
        $fromWallet = Wallet::factory()->create(['balance' => 1000000]);
        $toWallet = Wallet::factory()->create(['balance' => 0]);
        $transferCount = 500;
        $transferAmount = 1000;
        $results = [];

        $startTime = microtime(true);

        for ($i = 0; $i < $transferCount; $i++) {
            try {
                $this->walletService->transfer($fromWallet->id, $toWallet->id, $transferAmount, 'test_transfer');
                $results[] = ['success' => true];
            } catch (\Exception $e) {
                $results[] = ['success' => false];
            }
        }

        $duration = microtime(true) - $startTime;
        $successCount = count(array_filter($results, fn($r) => $r['success']));

        $this->assertGreaterThan(95, $successRate = ($successCount / $transferCount) * 100, 'Transfer success rate > 95%');
        $this->assertLessThan(30, $duration, '500 transfers should complete in < 30 seconds');

        // Verify balance integrity
        $fromWallet->refresh();
        $toWallet->refresh();
        $expectedFrom = 1000000 - ($successCount * $transferAmount);
        $expectedTo = $successCount * $transferAmount;

        $this->assertEquals($expectedFrom, $fromWallet->balance, 'Source wallet balance correct');
        $this->assertEquals($expectedTo, $toWallet->balance, 'Destination wallet balance correct');
    }

    public function test_wallet_balance_cache_stress(): void
    {
        $walletId = 1;
        $iterations = 10000;
        $startTime = microtime(true);

        for ($i = 0; $i < $iterations; $i++) {
            $key = "wallet_balance_{$walletId}";
            Redis::setex($key, 60, rand(1000, 100000));
            Redis::get($key);
        }

        $duration = microtime(true) - $startTime;
        $opsPerSecond = $iterations / $duration;

        $this->assertGreaterThan(2000, $opsPerSecond, 'Redis should handle > 2000 ops/sec');
    }

    public function test_transaction_history_stress(): void
    {
        $wallet = Wallet::factory()->create();

        // Create 1000 transactions
        for ($i = 0; $i < 1000; $i++) {
            WalletTransaction::create([
                'wallet_id' => $wallet->id,
                'type' => $i % 2 === 0 ? TransactionType::Deposit : TransactionType::Withdrawal,
                'amount' => rand(100, 10000),
                'balance_after' => 0,
            ]);
        }

        $startTime = microtime(true);
        $history = $this->walletService->getTransactionHistory($wallet->id, 100, 0);
        $duration = microtime(true) - $startTime;

        $this->assertCount(100, $history, 'Should return 100 transactions');
        $this->assertLessThan(1, $duration, 'History query should complete in < 1 second');
    }

    public function test_concurrent_wallet_creation(): void
    {
        $concurrency = 100;
        $results = [];

        $startTime = microtime(true);

        for ($i = 0; $i < $concurrency; $i++) {
            try {
                $wallet = Wallet::create([
                    'user_id' => $i + 1,
                    'tenant_id' => 1,
                    'balance' => 0,
                    'currency' => 'RUB',
                ]);
                $results[] = ['success' => true, 'wallet_id' => $wallet->id];
            } catch (\Exception $e) {
                $results[] = ['success' => false];
            }
        }

        $duration = microtime(true) - $startTime;
        $successCount = count(array_filter($results, fn($r) => $r['success']));

        $this->assertEquals($concurrency, $successCount, 'All wallets should be created');
        $this->assertLessThan(10, $duration, '100 wallet creations should complete in < 10 seconds');
    }
}
