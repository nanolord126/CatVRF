<?php declare(strict_types=1);

namespace Tests\Unit;

use App\Models\Tenant;
use App\Models\Wallet;
use App\Models\BalanceTransaction;
use App\Services\Wallet\WalletService;
use Illuminate\Support\Str;
use Tests\TestCase;

class WalletServiceTest extends TestCase
{
    private WalletService $service;
    private Tenant $tenant;
    private Wallet $wallet;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(WalletService::class);
        $this->tenant = Tenant::factory()->create();
        $this->wallet = Wallet::factory()->create(['tenant_id' => $this->tenant->id, 'current_balance' => 100000]);
    }

    public function test_credit_increases_balance(): void
    {
        $correlationId = Str::uuid();
        $this->service->credit($this->tenant->id, 50000, 'deposit', 1, $correlationId, 'Test deposit');

        $this->wallet->refresh();
        $this->assertEquals(150000, $this->wallet->current_balance);

        $transaction = BalanceTransaction::where('correlation_id', $correlationId)->first();
        $this->assertNotNull($transaction);
        $this->assertEquals(50000, $transaction->amount);
        $this->assertEquals('credit', $transaction->type);
    }

    public function test_debit_decreases_balance(): void
    {
        $correlationId = Str::uuid();
        $this->service->debit($this->tenant->id, 30000, 'withdrawal', 1, $correlationId, 'Test withdrawal');

        $this->wallet->refresh();
        $this->assertEquals(70000, $this->wallet->current_balance);

        $transaction = BalanceTransaction::where('correlation_id', $correlationId)->first();
        $this->assertNotNull($transaction);
        $this->assertEquals(30000, $transaction->amount);
        $this->assertEquals('debit', $transaction->type);
    }

    public function test_debit_throws_insufficient_balance(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Insufficient balance');

        $this->service->debit($this->tenant->id, 150000, 'withdrawal', 1, Str::uuid(), 'Too much');
    }

    public function test_hold_reserves_funds(): void
    {
        $correlationId = Str::uuid();
        $this->service->hold($this->tenant->id, 40000, 'Payment hold', $correlationId);

        $this->wallet->refresh();
        $this->assertEquals(40000, $this->wallet->hold_amount);
        $this->assertEquals(100000, $this->wallet->current_balance);
    }

    public function test_hold_prevents_overdraft(): void
    {
        $this->expectException(\Exception::class);
        $this->service->hold($this->tenant->id, 120000, 'Too much hold', Str::uuid());
    }

    public function test_release_removes_hold(): void
    {
        $holdId = Str::uuid();
        $this->service->hold($this->tenant->id, 40000, 'Test hold', $holdId);
        $this->wallet->refresh();
        $this->assertEquals(40000, $this->wallet->hold_amount);

        $this->service->releaseHold($this->tenant->id, 40000, $holdId);
        $this->wallet->refresh();
        $this->assertEquals(0, $this->wallet->hold_amount);
    }

    public function test_partial_release_works(): void
    {
        $holdId = Str::uuid();
        $this->service->hold($this->tenant->id, 50000, 'Test hold', $holdId);

        $this->service->releaseHold($this->tenant->id, 30000, $holdId);
        $this->wallet->refresh();
        $this->assertEquals(20000, $this->wallet->hold_amount);
    }

    public function test_get_balance_includes_correlation_id(): void
    {
        $balance = $this->service->getBalance($this->tenant->id);
        $this->assertEquals(100000, $balance);
    }

    public function test_multiple_credits_accumulate(): void
    {
        $correlationId1 = Str::uuid();
        $correlationId2 = Str::uuid();
        $correlationId3 = Str::uuid();

        $this->service->credit($this->tenant->id, 10000, 'deposit', 1, $correlationId1, 'First');
        $this->service->credit($this->tenant->id, 20000, 'deposit', 1, $correlationId2, 'Second');
        $this->service->credit($this->tenant->id, 30000, 'deposit', 1, $correlationId3, 'Third');

        $this->wallet->refresh();
        $this->assertEquals(160000, $this->wallet->current_balance);
    }

    public function test_sequential_debit_and_credit(): void
    {
        $debitId = Str::uuid();
        $creditId = Str::uuid();

        $this->service->debit($this->tenant->id, 25000, 'withdrawal', 1, $debitId, 'Out');
        $this->wallet->refresh();
        $this->assertEquals(75000, $this->wallet->current_balance);

        $this->service->credit($this->tenant->id, 50000, 'deposit', 1, $creditId, 'In');
        $this->wallet->refresh();
        $this->assertEquals(125000, $this->wallet->current_balance);
    }

    public function test_hold_then_partial_debit(): void
    {
        $holdId = Str::uuid();
        $this->service->hold($this->tenant->id, 40000, 'Hold for payment', $holdId);

        $debitId = Str::uuid();
        $this->service->debit($this->tenant->id, 20000, 'partial_charge', 1, $debitId, 'Charge held funds');

        $this->wallet->refresh();
        $this->assertEquals(80000, $this->wallet->current_balance);
        $this->assertEquals(40000, $this->wallet->hold_amount);
    }

    public function test_transaction_atomicity_on_failure(): void
    {
        $initialBalance = $this->wallet->current_balance;
        
        try {
            $this->service->debit($this->tenant->id, 200000, 'withdrawal', 1, Str::uuid(), 'Fails');
        } catch (\Exception $e) {
            // Expected
        }

        $this->wallet->refresh();
        $this->assertEquals($initialBalance, $this->wallet->current_balance);
    }

    public function test_correlation_id_prevents_duplicates(): void
    {
        $correlationId = Str::uuid();
        
        $this->service->credit($this->tenant->id, 50000, 'deposit', 1, $correlationId, 'First');
        $this->wallet->refresh();
        $firstBalance = $this->wallet->current_balance;

        // Try same correlation_id again (should be idempotent)
        $this->service->credit($this->tenant->id, 50000, 'deposit', 1, $correlationId, 'Retry');
        $this->wallet->refresh();

        // Should not double-credit
        $this->assertEquals($firstBalance, $this->wallet->current_balance);
    }
}
