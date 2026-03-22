<?php declare(strict_types=1);

use App\Models\Wallet;
use App\Models\BalanceTransaction;
use App\Services\FraudControlService;
use App\Services\Wallet\WalletService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->fraudControl = Mockery::mock(FraudControlService::class);
    $this->fraudControl->shouldReceive('check')->andReturn([
        'score' => 0.1,
        'decision' => 'allow',
        'threshold' => 0.7,
    ]);

    $this->walletService = new WalletService($this->fraudControl);
});

test('can create wallet', function () {
    $wallet = $this->walletService->createWallet(tenantId: 1, initialBalance: 10000);

    expect($wallet)->toBeInstanceOf(Wallet::class)
        ->and($wallet->current_balance)->toBe(10000)
        ->and($wallet->hold_amount)->toBe(0);
});

test('can credit wallet', function () {
    $wallet = Wallet::factory()->create(['current_balance' => 5000]);

    $transaction = $this->walletService->credit(
        walletId: $wallet->id,
        amount: 3000,
        type: 'deposit',
        correlationId: 'test-correlation-id',
    );

    expect($transaction)->toBeInstanceOf(BalanceTransaction::class)
        ->and($transaction->amount)->toBe(3000)
        ->and($transaction->balance_after)->toBe(8000);

    $wallet->refresh();
    expect($wallet->current_balance)->toBe(8000);
});

test('can debit wallet', function () {
    $wallet = Wallet::factory()->create(['current_balance' => 10000]);

    $transaction = $this->walletService->debit(
        walletId: $wallet->id,
        amount: 3000,
        type: 'withdrawal',
        correlationId: 'test-correlation-id',
    );

    expect($transaction)->toBeInstanceOf(BalanceTransaction::class)
        ->and($transaction->amount)->toBe(-3000)
        ->and($transaction->balance_after)->toBe(7000);

    $wallet->refresh();
    expect($wallet->current_balance)->toBe(7000);
});

test('cannot debit more than available balance', function () {
    $wallet = Wallet::factory()->create(['current_balance' => 1000]);

    $this->walletService->debit(
        walletId: $wallet->id,
        amount: 2000,
        type: 'withdrawal',
        correlationId: 'test-correlation-id',
    );
})->throws(RuntimeException::class, 'Insufficient balance');

test('can hold amount', function () {
    $wallet = Wallet::factory()->create(['current_balance' => 10000, 'hold_amount' => 0]);

    $result = $this->walletService->hold(
        walletId: $wallet->id,
        amount: 3000,
        reason: 'Test hold',
        correlationId: 'test-correlation-id',
    );

    expect($result)->toBeTrue();

    $wallet->refresh();
    expect($wallet->hold_amount)->toBe(3000);
});

test('can release hold', function () {
    $wallet = Wallet::factory()->create(['current_balance' => 10000, 'hold_amount' => 3000]);

    $result = $this->walletService->release(
        walletId: $wallet->id,
        amount: 3000,
        correlationId: 'test-correlation-id',
    );

    expect($result)->toBeTrue();

    $wallet->refresh();
    expect($wallet->hold_amount)->toBe(0);
});

test('cannot release more than held amount', function () {
    $wallet = Wallet::factory()->create(['current_balance' => 10000, 'hold_amount' => 1000]);

    $this->walletService->release(
        walletId: $wallet->id,
        amount: 2000,
        correlationId: 'test-correlation-id',
    );
})->throws(RuntimeException::class);

test('getBalance returns cached value', function () {
    $wallet = Wallet::factory()->create(['tenant_id' => '1', 'current_balance' => 5000]);

    $balance1 = $this->walletService->getBalance(tenantId: '1');
    $balance2 = $this->walletService->getBalance(tenantId: '1');

    expect($balance1)->toBe(5000)
        ->and($balance2)->toBe(5000);
});

test('credit invalidates cache', function () {
    $wallet = Wallet::factory()->create(['tenant_id' => '1', 'current_balance' => 5000]);

    Cache::shouldReceive('forget')->once()->with('wallet:balance:tenant:1');

    $this->walletService->credit(
        tenantId: '1',
        amount: 1000,
        type: 'deposit',
        correlationId: 'test-correlation-id',
    );
});

test('fraud check blocks credit when decision is block', function () {
    $fraudControl = Mockery::mock(FraudControlService::class);
    $fraudControl->shouldReceive('check')->andReturn([
        'score' => 0.9,
        'decision' => 'block',
        'threshold' => 0.7,
    ]);

    $walletService = new WalletService($fraudControl);
    $wallet = Wallet::factory()->create(['current_balance' => 5000]);

    $walletService->credit(
        walletId: $wallet->id,
        amount: 10000,
        type: 'deposit',
        correlationId: 'test-correlation-id',
    );
})->throws(RuntimeException::class, 'Operation blocked by fraud detection system');
