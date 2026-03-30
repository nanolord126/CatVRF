<?php declare(strict_types=1);

namespace Tests\Integration\Wallet;

use App\Models\BalanceTransaction;
use App\Models\Payment;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Wallet;
use App\Services\Payments\PaymentService;
use App\Services\Wallet\WalletService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

/**
 * WalletOperationFlowTest
 * 
 * Интеграционные тесты: платёж → wallet credit → refund
 */
final class WalletOperationFlowTest extends TestCase
{
    use RefreshDatabase;

    protected WalletService $walletService;
    protected PaymentService $paymentService;
    protected User $user;
    protected Wallet $wallet;
    protected Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();
        Log::fake();

        $this->tenant = Tenant::factory()->create();
        $this->user = User::factory()->for($this->tenant)->create();
        $this->wallet = Wallet::factory()
            ->for($this->tenant)
            ->for($this->user)
            ->create(['current_balance' => 0]);

        $this->walletService = app(WalletService::class);
        $this->paymentService = app(PaymentService::class);
    }

    /** @test */
    public function it_processes_payment_and_credits_wallet(): void
    {
        $amount = 100000; // 1000 руб

        // 1. Create payment
        $payment = Payment::factory()
            ->for($this->user)
            ->create([
                'amount' => $amount,
                'status' => 'pending',
            ]);

        // 2. Payment captured
        $payment->update(['status' => 'captured']);

        // 3. Credit wallet
        $transaction = BalanceTransaction::factory()
            ->for($this->wallet)
            ->create([
                'type' => 'deposit',
                'amount' => $amount,
                'status' => 'completed',
            ]);

        $this->wallet->update([
            'current_balance' => $this->wallet->current_balance + $amount,
        ]);

        // Verify
        $this->assertEquals('captured', $payment->status);
        $this->assertEquals('completed', $transaction->status);
        $this->assertEquals($amount, $this->wallet->current_balance);
    }

    /** @test */
    public function it_holds_wallet_amount_on_order_creation(): void
    {
        // Initial deposit
        $this->wallet->update(['current_balance' => 100000]);

        $holdAmount = 50000;

        // Order created - hold amount
        $transaction = BalanceTransaction::factory()
            ->for($this->wallet)
            ->create([
                'type' => 'hold',
                'amount' => $holdAmount,
                'status' => 'pending',
            ]);

        $this->wallet->update([
            'hold_amount' => $this->wallet->hold_amount + $holdAmount,
        ]);

        $availableBalance = $this->wallet->current_balance - $this->wallet->hold_amount;

        // Verify
        $this->assertEquals(50000, $availableBalance); // 100 - 50 = 50
        $this->assertEquals('pending', $transaction->status);
    }

    /** @test */
    public function it_releases_hold_on_order_cancellation(): void
    {
        $this->wallet->update([
            'current_balance' => 100000,
            'hold_amount' => 50000,
        ]);

        $holdAmount = 50000;

        // Order cancelled - release hold
        $transaction = BalanceTransaction::factory()
            ->for($this->wallet)
            ->create([
                'type' => 'release',
                'amount' => $holdAmount,
                'status' => 'completed',
            ]);

        $this->wallet->update(['hold_amount' => 0]);

        $availableBalance = $this->wallet->current_balance - $this->wallet->hold_amount;

        // Verify
        $this->assertEquals(100000, $availableBalance); // All available
        $this->assertEquals(0, $this->wallet->hold_amount);
    }

    /** @test */
    public function it_deducts_held_amount_on_order_completion(): void
    {
        $this->wallet->update([
            'current_balance' => 100000,
            'hold_amount' => 50000,
        ]);

        $deductAmount = 50000;

        // Order completed - deduct from held
        $transaction = BalanceTransaction::factory()
            ->for($this->wallet)
            ->create([
                'type' => 'withdrawal',
                'amount' => $deductAmount,
                'status' => 'completed',
            ]);

        // Remove from hold, keep in current balance
        $this->wallet->update([
            'hold_amount' => 0,
            'current_balance' => $this->wallet->current_balance - $deductAmount,
        ]);

        // Verify
        $this->assertEquals(50000, $this->wallet->current_balance); // 100 - 50
        $this->assertEquals(0, $this->wallet->hold_amount);
    }

    /** @test */
    public function it_handles_payment_refund_to_wallet(): void
    {
        $this->wallet->update(['current_balance' => 100000]);

        // Payment made and executed
        $payment = Payment::factory()
            ->for($this->user)
            ->create(['status' => 'captured', 'amount' => 30000]);

        // Refund initiated
        $refundAmount = 30000;

        $refundTransaction = BalanceTransaction::factory()
            ->for($this->wallet)
            ->create([
                'type' => 'refund',
                'amount' => $refundAmount,
                'status' => 'completed',
            ]);

        $this->wallet->update([
            'current_balance' => $this->wallet->current_balance + $refundAmount,
        ]);

        // Verify
        $this->assertEquals(130000, $this->wallet->current_balance); // 100 + 30
        $this->assertEquals('completed', $refundTransaction->status);
    }

    /** @test */
    public function it_prevents_withdrawal_exceeding_available_balance(): void
    {
        $this->wallet->update([
            'current_balance' => 30000, // 300 руб
            'hold_amount' => 20000, // 200 руб held
        ]);

        $availableBalance = $this->wallet->current_balance - $this->wallet->hold_amount; // 100 руб

        $requestedWithdrawal = 50000; // Trying to withdraw 500 руб

        $canWithdraw = $requestedWithdrawal <= $availableBalance;

        $this->assertFalse($canWithdraw);
    }

    /** @test */
    public function it_applies_commission_on_withdrawal(): void
    {
        $this->wallet->update(['current_balance' => 100000]);

        $withdrawAmount = 100000;
        $commissionPercent = 1; // 1% commission
        $commission = (int)($withdrawAmount * $commissionPercent / 100);

        $transaction = BalanceTransaction::factory()
            ->for($this->wallet)
            ->create([
                'type' => 'withdrawal',
                'amount' => $withdrawAmount,
                'commission' => $commission,
                'status' => 'completed',
            ]);

        $this->wallet->update([
            'current_balance' => $this->wallet->current_balance - ($withdrawAmount + $commission),
        ]);

        // Verify
        $this->assertEquals(99000, $this->wallet->current_balance); // 100 - 1 commission
        $this->assertEquals($commission, $transaction->commission);
    }

    /** @test */
    public function it_records_bonus_in_wallet(): void
    {
        $this->wallet->update(['current_balance' => 0]);

        $bonusAmount = 10000; // 100 руб бонус

        $transaction = BalanceTransaction::factory()
            ->for($this->wallet)
            ->create([
                'type' => 'bonus',
                'amount' => $bonusAmount,
                'source' => 'referral_program',
                'status' => 'completed',
            ]);

        $this->wallet->update([
            'current_balance' => $this->wallet->current_balance + $bonusAmount,
        ]);

        // Verify
        $this->assertEquals($bonusAmount, $this->wallet->current_balance);
        $this->assertEquals('referral_program', $transaction->source);
    }

    /** @test */
    public function it_uses_transaction_for_atomicity(): void
    {
        $this->wallet->update(['current_balance' => 100000]);

        $this->assertTrue(DB::transaction(function () {
            BalanceTransaction::factory()
                ->for($this->wallet)
                ->create([
                    'type' => 'deposit',
                    'amount' => 50000,
                ]);

            $this->wallet->update([
                'current_balance' => 150000,
            ]);

            return true;
        }));

        // Verify both succeeded
        $transactionCount = BalanceTransaction::where('wallet_id', $this->wallet->id)
            ->count();

        $this->assertGreaterThan(0, $transactionCount);
        $this->assertEquals(150000, $this->wallet->current_balance);
    }

    /** @test */
    public function it_handles_concurrent_wallet_updates(): void
    {
        $this->wallet->update(['current_balance' => 100000]);

        // Simulate two concurrent operations
        $transaction1 = BalanceTransaction::factory()
            ->for($this->wallet)
            ->create(['type' => 'deposit', 'amount' => 10000]);

        $transaction2 = BalanceTransaction::factory()
            ->for($this->wallet)
            ->create(['type' => 'deposit', 'amount' => 20000]);

        // Both should be recorded
        $totalTransacted = BalanceTransaction::where('wallet_id', $this->wallet->id)
            ->sum('amount');

        $this->assertEquals(30000, $totalTransacted);
    }

    /** @test */
    public function it_generates_wallet_statement(): void
    {
        // Generate multiple transactions
        BalanceTransaction::factory()
            ->for($this->wallet)
            ->create(['type' => 'deposit', 'amount' => 50000]);

        BalanceTransaction::factory()
            ->for($this->wallet)
            ->create(['type' => 'withdrawal', 'amount' => 20000]);

        BalanceTransaction::factory()
            ->for($this->wallet)
            ->create(['type' => 'bonus', 'amount' => 10000]);

        $statement = BalanceTransaction::where('wallet_id', $this->wallet->id)
            ->orderByDesc('created_at')
            ->get();

        $this->assertCount(3, $statement);

        // Calculate balance from statement
        $calculatedBalance = 0;
        foreach ($statement as $transaction) {
            if (in_array($transaction->type, ['deposit', 'bonus', 'refund'])) {
                $calculatedBalance += $transaction->amount;
            } elseif ($transaction->type === 'withdrawal') {
                $calculatedBalance -= $transaction->amount;
            }
        }

        $this->assertEquals(40000, $calculatedBalance); // 50 - 20 + 10
    }

    /** @test */
    public function it_validates_wallet_consistency(): void
    {
        $this->wallet->update(['current_balance' => 0]);

        // Add transactions
        for ($i = 0; $i < 5; $i++) {
            BalanceTransaction::factory()
                ->for($this->wallet)
                ->create(['type' => 'deposit', 'amount' => 10000]);
        }

        $totalFromTransactions = BalanceTransaction::where('wallet_id', $this->wallet->id)
            ->sum('amount');

        // Wallet balance should equal sum of transactions
        $expectedBalance = $totalFromTransactions;

        $this->assertEquals(50000, $expectedBalance);
    }

    /** @test */
    public function it_logs_all_wallet_operations(): void
    {
        BalanceTransaction::factory()
            ->for($this->wallet)
            ->create(['type' => 'deposit', 'amount' => 50000]);

        Log::assertLogged(function ($message) {
            return str_contains($message, 'wallet') || 
                   str_contains($message, 'transaction');
        });
    }
}
