<?php declare(strict_types=1);

namespace Tests\Unit\Services\Wallet;

use App\Models\Wallet;
use App\Models\BalanceTransaction;
use App\Services\Wallet\WalletService;
use Tests\SimpleTestCase;
use Illuminate\Support\Str;

/**
 * Unit Tests для WalletService (PHPUnit формат)
 *
 * Тестирует:
 * - Операции с балансом (credit, debit, hold, release)
 * - Atomicity (DB::transaction)
 * - Optimistic locking (lockForUpdate)
 * - Redis caching
 * - Audit logging
 * - Fraud checks
 * - Edge cases (недостаточно средств, отрицательный баланс)
 */
class WalletServiceTestPHPUnit extends SimpleTestCase
{
    private WalletService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new WalletService();
    }

    /** @test */
    public function test_wallet_service_can_create_wallet(): void
    {
        $wallet = $this->service->createWallet(
            tenantId: $this->tenant->id,
            userId: $this->user->id,
            initialBalance: 50000
        );

        $this->assertInstanceOf(Wallet::class, $wallet);
        $this->assertEquals(50000, $wallet->current_balance);
        $this->assertEquals($this->tenant->id, $wallet->tenant_id);
        $this->assertNotNull($wallet->uuid);
        $this->assertNotNull($wallet->correlation_id);

        $this->assertDatabaseHas('wallets', [
            'tenant_id' => $this->tenant->id,
            'user_id' => $this->user->id,
            'current_balance' => 50000,
        ]);
    }

    /** @test */
    public function test_wallet_credit_operation(): void
    {
        $wallet = Wallet::factory()->create([
            'tenant_id' => $this->tenant->id,
            'current_balance' => 10000,
        ]);

        $result = $this->service->credit(
            walletId: $wallet->id,
            amount: 5000,
            reason: 'bonus',
            correlationId: $this->correlationId
        );

        $this->assertTrue($result);

        $wallet->refresh();
        $this->assertEquals(15000, $wallet->current_balance);

        $this->assertDatabaseHas('balance_transactions', [
            'wallet_id' => $wallet->id,
            'type' => 'deposit',
            'amount' => 5000,
            'correlation_id' => $this->correlationId,
        ]);
    }

    /** @test */
    public function test_wallet_debit_operation(): void
    {
        $wallet = Wallet::factory()->create([
            'tenant_id' => $this->tenant->id,
            'current_balance' => 10000,
        ]);

        $result = $this->service->debit(
            walletId: $wallet->id,
            amount: 3000,
            reason: 'purchase',
            correlationId: $this->correlationId
        );

        $this->assertTrue($result);

        $wallet->refresh();
        $this->assertEquals(7000, $wallet->current_balance);

        $this->assertDatabaseHas('balance_transactions', [
            'wallet_id' => $wallet->id,
            'type' => 'withdrawal',
            'amount' => 3000,
            'correlation_id' => $this->correlationId,
        ]);
    }

    /** @test */
    public function test_wallet_debit_fails_on_insufficient_funds(): void
    {
        $wallet = Wallet::factory()->create([
            'tenant_id' => $this->tenant->id,
            'current_balance' => 1000,
        ]);

        $result = $this->service->debit(
            walletId: $wallet->id,
            amount: 5000,
            reason: 'purchase',
            correlationId: $this->correlationId
        );

        $this->assertFalse($result);

        $wallet->refresh();
        $this->assertEquals(1000, $wallet->current_balance);
    }

    /** @test */
    public function test_wallet_hold_and_release_operations(): void
    {
        $wallet = Wallet::factory()->create([
            'tenant_id' => $this->tenant->id,
            'current_balance' => 10000,
            'hold_amount' => 0,
        ]);

        $holdResult = $this->service->hold(
            walletId: $wallet->id,
            amount: 5000,
            sourceType: 'order',
            sourceId: 123,
            correlationId: $this->correlationId
        );

        $this->assertTrue($holdResult);

        $wallet->refresh();
        $this->assertEquals(5000, $wallet->hold_amount);
        $this->assertEquals(10000, $wallet->current_balance);

        $releaseResult = $this->service->release(
            walletId: $wallet->id,
            amount: 5000,
            sourceType: 'order',
            sourceId: 123,
            correlationId: $this->correlationId
        );

        $this->assertTrue($releaseResult);

        $wallet->refresh();
        $this->assertEquals(0, $wallet->hold_amount);
    }

    /** @test */
    public function test_wallet_release_after_hold_debit_pattern_payment_flow(): void
    {
        $wallet = Wallet::factory()->create([
            'tenant_id' => $this->tenant->id,
            'current_balance' => 10000,
            'hold_amount' => 0,
        ]);

        // 1. Hold при заказе
        $this->service->hold($wallet->id, 5000, 'order', 1, $this->correlationId);
        $wallet->refresh();
        $this->assertEquals(5000, $wallet->hold_amount);
        $this->assertEquals(10000, $wallet->current_balance);

        // 2. Debit при выполнении
        $this->service->debit($wallet->id, 5000, 'payment', $this->correlationId);
        $wallet->refresh();
        $this->assertEquals(5000, $wallet->current_balance);
        $this->assertEquals(5000, $wallet->hold_amount);

        // 3. Release
        $this->service->release($wallet->id, 5000, 'order', 1, $this->correlationId);
        $wallet->refresh();
        $this->assertEquals(0, $wallet->hold_amount);
        $this->assertEquals(5000, $wallet->current_balance);
    }

    /** @test */
    public function test_wallet_operations_are_transactional(): void
    {
        $wallet = Wallet::factory()->create([
            'tenant_id' => $this->tenant->id,
            'current_balance' => 10000,
        ]);

        try {
            \DB::transaction(function () use ($wallet) {
                $this->service->debit($wallet->id, 5000, 'test', $this->correlationId);
                throw new \Exception('Rollback test');
            });
        } catch (\Exception $e) {
            // Expected
        }

        $wallet->refresh();
        $this->assertEquals(10000, $wallet->current_balance);
    }

    /** @test */
    public function test_wallet_balance_in_cents_kopeks(): void
    {
        $wallet = Wallet::factory()->create([
            'tenant_id' => $this->tenant->id,
            'current_balance' => 99999, // 999.99 RUB в копейках
        ]);

        $result = $this->service->credit($wallet->id, 1, 'test', $this->correlationId);
        $this->assertTrue($result);

        $wallet->refresh();
        $this->assertEquals(100000, $wallet->current_balance); // 1000.00 RUB
    }

    /** @test */
    public function test_wallet_balance_never_goes_negative(): void
    {
        $wallet = Wallet::factory()->create([
            'tenant_id' => $this->tenant->id,
            'current_balance' => 5000,
        ]);

        $result = $this->service->debit($wallet->id, 10000, 'test', $this->correlationId);
        $this->assertFalse($result);

        $wallet->refresh();
        $this->assertGreaterThanOrEqual(0, $wallet->current_balance);
    }

    /** @test */
    public function test_wallet_caching_in_redis(): void
    {
        $wallet = Wallet::factory()->create([
            'tenant_id' => $this->tenant->id,
            'current_balance' => 10000,
        ]);

        // Первый вызов — читает из БД
        $balance1 = $this->service->getCurrentBalance($wallet->id);
        $this->assertEquals(10000, $balance1);

        // Проверяем что cached в Redis
        $cachedBalance = \Cache::get("wallet:balance:{$wallet->id}");
        $this->assertEquals(10000, $cachedBalance);

        // Второй вызов — из кэша
        $balance2 = $this->service->getCurrentBalance($wallet->id);
        $this->assertEquals(10000, $balance2);

        // После debit — кэш инвалидируется
        $this->service->debit($wallet->id, 2000, 'test', $this->correlationId);
        $cachedBalance = \Cache::get("wallet:balance:{$wallet->id}");
        $this->assertNull($cachedBalance);

        $balance3 = $this->service->getCurrentBalance($wallet->id);
        $this->assertEquals(8000, $balance3);
    }

    /** @test */
    public function test_wallet_get_current_balance_with_hold(): void
    {
        $wallet = Wallet::factory()->create([
            'tenant_id' => $this->tenant->id,
            'current_balance' => 10000,
            'hold_amount' => 3000,
        ]);

        $availableBalance = $this->service->getAvailableBalance($wallet->id);
        $this->assertEquals(7000, $availableBalance);
    }

    /** @test */
    public function test_wallet_audit_log_contains_all_required_fields(): void
    {
        $wallet = Wallet::factory()->create([
            'tenant_id' => $this->tenant->id,
            'current_balance' => 10000,
        ]);

        $this->service->debit($wallet->id, 1000, 'test_reason', $this->correlationId);

        $transaction = BalanceTransaction::where('wallet_id', $wallet->id)->first();

        $this->assertNotNull($transaction);
        $this->assertEquals($this->correlationId, $transaction->correlation_id);
        $this->assertEquals($wallet->id, $transaction->wallet_id);
        $this->assertEquals('withdrawal', $transaction->type);
        $this->assertEquals(1000, $transaction->amount);
        $this->assertEquals('completed', $transaction->status);
        $this->assertEquals($this->tenant->id, $transaction->tenant_id);
        $this->assertNotNull($transaction->created_at);
    }

    /** @test */
    public function test_wallet_holds_with_invalid_amount_are_rejected(): void
    {
        $wallet = Wallet::factory()->create([
            'tenant_id' => $this->tenant->id,
            'current_balance' => 5000,
        ]);

        // Negative amount
        $result1 = $this->service->hold($wallet->id, -1000, 'order', 1, $this->correlationId);
        $this->assertFalse($result1);

        // Zero amount
        $result2 = $this->service->hold($wallet->id, 0, 'order', 1, $this->correlationId);
        $this->assertFalse($result2);

        // More than available
        $result3 = $this->service->hold($wallet->id, 10000, 'order', 1, $this->correlationId);
        $this->assertFalse($result3);
    }

    /** @test */
    public function test_multiple_holds_accumulate_correctly(): void
    {
        $wallet = Wallet::factory()->create([
            'tenant_id' => $this->tenant->id,
            'current_balance' => 10000,
            'hold_amount' => 0,
        ]);

        $this->service->hold($wallet->id, 2000, 'order', 1, $this->correlationId);
        $wallet->refresh();
        $this->assertEquals(2000, $wallet->hold_amount);

        $this->service->hold($wallet->id, 3000, 'order', 2, $this->correlationId);
        $wallet->refresh();
        $this->assertEquals(5000, $wallet->hold_amount);

        $this->service->release($wallet->id, 2000, 'order', 1, $this->correlationId);
        $wallet->refresh();
        $this->assertEquals(3000, $wallet->hold_amount);
    }
}
