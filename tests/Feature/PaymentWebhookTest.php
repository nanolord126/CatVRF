<?php declare(strict_types=1);

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Wallet;
use App\Models\Tenant;
use App\Models\User;
use App\Models\PaymentTransaction;
use App\Services\Payment\IdempotencyService;
use App\Services\Wallet\WalletService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

final class PaymentWebhookTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;
    private User $user;
    private Wallet $wallet;
    private IdempotencyService $idempotencyService;
    private WalletService $walletService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::factory()->create();
        $this->user = User::factory()->create(['tenant_id' => $this->tenant->id]);
        $this->wallet = Wallet::factory()->create([
            'tenant_id' => $this->tenant->id,
            'current_balance' => 500000, // 5000 руб
        ]);

        $this->idempotencyService = app(IdempotencyService::class);
        $this->walletService = app(WalletService::class);
    }

    public function test_wallet_credit_transaction_creates_balance_transaction(): void
    {
        $initialBalance = $this->wallet->current_balance;
        $amount = 100000; // 1000 руб

        $transaction = $this->walletService->credit(
            tenantId: $this->tenant->id,
            amount: $amount,
            type: 'deposit',
            correlationId: 'test-deposit-' . now()->timestamp,
        );

        $this->assertNotNull($transaction);
        $this->assertEquals($amount, $transaction->amount);
        $this->assertEquals('credit', $transaction->type);

        $this->wallet->refresh();
        $this->assertEquals($initialBalance + $amount, $this->wallet->current_balance);
    }

    public function test_wallet_debit_with_sufficient_balance(): void
    {
        $initialBalance = $this->wallet->current_balance;
        $amount = 100000;

        $transaction = $this->walletService->debit(
            tenantId: $this->tenant->id,
            amount: $amount,
            type: 'withdrawal',
            correlationId: 'test-withdrawal-' . now()->timestamp,
        );

        $this->assertNotNull($transaction);
        $this->assertEquals($amount, $transaction->amount);
        $this->assertEquals('debit', $transaction->type);

        $this->wallet->refresh();
        $this->assertEquals($initialBalance - $amount, $this->wallet->current_balance);
    }

    public function test_idempotency_prevents_duplicate_payments(): void
    {
        $payload = ['amount' => 100000, 'currency' => 'RUB'];
        $idempotencyKey = 'test-key-' . now()->timestamp;
        $correlationId = 'test-corr-' . now()->timestamp;

        // First call should succeed
        $first = $this->idempotencyService->check(
            idempotencyKey: $idempotencyKey,
            merchantId: $this->tenant->id,
            payload: $payload,
            correlationId: $correlationId,
        );

        $this->assertTrue($first);

        // Second call with same key should return false (already exists)
        $second = $this->idempotencyService->check(
            idempotencyKey: $idempotencyKey,
            merchantId: $this->tenant->id,
            payload: $payload,
            correlationId: $correlationId,
        );

        $this->assertFalse($second);
    }

    public function test_tinkoff_webhook_payment_notification(): void
    {
        $paymentId = 'tinkoff-' . now()->timestamp;

        $webhookPayload = [
            'TerminalKey' => 'TESTMERCHANT',
            'OrderId' => '12345',
            'Success' => true,
            'Status' => 'CONFIRMED',
            'PaymentId' => $paymentId,
            'Amount' => 100000,
            'Currency' => '643',
            'Date' => now()->timestamp * 1000,
            'Token' => 'mock-token',
        ];

        $response = $this->postJson('/internal/webhooks/tinkoff', $webhookPayload);

        $this->assertIn($response->status(), [200, 201]);

        $this->assertDatabaseHas('payment_transactions', [
            'provider_payment_id' => $paymentId,
            'status' => 'captured',
        ]);
    }

    public function test_wallet_hold_and_release(): void
    {
        $initialBalance = $this->wallet->current_balance;
        $holdAmount = 100000;

        // Place hold
        $held = $this->walletService->hold(
            tenantId: $this->tenant->id,
            amount: $holdAmount,
            correlationId: 'test-hold-' . now()->timestamp,
        );

        $this->assertTrue($held);

        $this->wallet->refresh();
        $this->assertEquals($holdAmount, $this->wallet->hold_amount);
        $this->assertEquals($initialBalance, $this->wallet->current_balance); // Balance unchanged

        // Release hold
        $released = $this->walletService->release(
            tenantId: $this->tenant->id,
            amount: $holdAmount,
            correlationId: 'test-release-' . now()->timestamp,
        );

        $this->assertTrue($released);

        $this->wallet->refresh();
        $this->assertEquals(0, $this->wallet->hold_amount);
    }

    public function test_payment_transaction_idempotency_key_uniqueness(): void
    {
        $idempotencyKey = 'unique-payment-' . now()->timestamp;

        PaymentTransaction::factory()->create([
            'tenant_id' => $this->tenant->id,
            'idempotency_key' => $idempotencyKey,
        ]);

        // Should not allow duplicate idempotency key
        $this->expectException(\Illuminate\Database\QueryException::class);

        PaymentTransaction::factory()->create([
            'tenant_id' => $this->tenant->id,
            'idempotency_key' => $idempotencyKey,
        ]);
    }

    public function test_wallet_api_endpoint_returns_balance(): void
    {
        $this->actingAs($this->user)
            ->getJson("/api/v1/wallets/{$this->wallet->id}")
            ->assertOk()
            ->assertJsonPath('data.current_balance', $this->wallet->current_balance)
            ->assertJsonPath('data.correlation_id', '*');
    }

    public function test_wallet_deposit_endpoint(): void
    {
        $this->actingAs($this->user)
            ->postJson("/api/v1/wallets/{$this->wallet->id}/deposit", [
                'amount' => 50000,
            ])
            ->assertCreated()
            ->assertJsonPath('data.type', 'deposit');

        $this->wallet->refresh();
        $this->assertEquals(550000, $this->wallet->current_balance);
    }
}
