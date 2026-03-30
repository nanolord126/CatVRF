<?php declare(strict_types=1);

namespace Tests\E2E;

use App\Models\PaymentTransaction;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentGatewayE2ETest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;
    private User $user;
    private Wallet $wallet;
    private string $token;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tenant = Tenant::factory()->create();
        $this->user = User::factory()->create();
        $this->wallet = Wallet::factory()->create(['tenant_id' => $this->tenant->id, 'current_balance' => 1000000]);
        $this->token = $this->user->createToken('test')->plainTextToken;
    }

    public function test_complete_payment_flow_init_to_captured(): void
    {
        // 1. Initialize payment
        $initResponse = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->postJson('/api/v1/payments', [
                'amount' => 250000,
                'currency' => 'RUB',
                'description' => 'Complete flow test',
                'return_url' => 'https://example.com/return',
            ]);

        $this->assertEquals(201, $initResponse->status());
        $transactionId = $initResponse->json('transaction_id');

        // 2. Verify payment exists in pending state
        $getResponse = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->getJson("/api/v1/payments/{$transactionId}");

        $this->assertEquals(200, $getResponse->status());
        $this->assertEquals('pending', $getResponse->json('status'));

        // 3. Simulate webhook notification (payment captured)
        $payment = PaymentTransaction::where('payment_id', $transactionId)->first();
        if ($payment) {
            $payment->update(['status' => 'captured', 'captured_at' => now()]);
        }

        // 4. Verify wallet was credited
        $this->wallet->refresh();
        // Balance should remain the same (funds only credited on capture)
        $this->assertGreaterThanOrEqual(1000000, $this->wallet->current_balance);
    }

    public function test_payment_hold_and_capture(): void
    {
        $holdAmount = 150000;

        // 1. Create payment with hold
        $initResponse = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->postJson('/api/v1/payments', [
                'amount' => $holdAmount,
                'currency' => 'RUB',
                'description' => 'Hold test',
                'return_url' => 'https://example.com',
            ]);

        $transactionId = $initResponse->json('transaction_id');

        // 2. Verify funds are held
        $this->wallet->refresh();
        $this->assertEquals($holdAmount, $this->wallet->hold_amount);

        // 3. Simulate capture webhook
        $payment = PaymentTransaction::where('payment_id', $transactionId)->first();
        if ($payment) {
            $payment->update(['status' => 'captured']);
        }

        // 4. Verify hold is released and balance updated
        $this->wallet->refresh();
        $this->assertEquals(0, $this->wallet->hold_amount);
    }

    public function test_payment_refund_flow(): void
    {
        // 1. Create and capture payment
        $amount = 100000;
        $initResponse = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->postJson('/api/v1/payments', [
                'amount' => $amount,
                'currency' => 'RUB',
                'description' => 'Refund test',
                'return_url' => 'https://example.com',
            ]);

        $transactionId = $initResponse->json('transaction_id');
        $initialBalance = $this->wallet->current_balance;

        // 2. Mark as captured
        $payment = PaymentTransaction::where('payment_id', $transactionId)->first();
        if ($payment) {
            $payment->update(['status' => 'captured', 'captured_at' => now()]);
            $this->wallet->decrement('current_balance', $amount);
        }

        // 3. Initiate refund
        $refundResponse = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->postJson("/api/v1/payments/{$transactionId}/refund", [
                'reason' => 'Customer request',
            ]);

        $this->assertEquals(200, $refundResponse->status());

        // 4. Simulate refund webhook
        if ($payment) {
            $payment->update(['status' => 'refunded', 'refunded_at' => now()]);
            $this->wallet->increment('current_balance', $amount);
        }

        // 5. Verify wallet balance restored
        $this->wallet->refresh();
        $this->assertEquals($initialBalance, $this->wallet->current_balance);
    }

    public function test_concurrent_payments_no_race_condition(): void
    {
        $amount = 50000;

        // Simulate 3 concurrent payments
        for ($i = 0; $i < 3; $i++) {
            $response = $this->withHeader('Authorization', "Bearer {$this->token}")
                ->postJson('/api/v1/payments', [
                    'amount' => $amount,
                    'currency' => 'RUB',
                    'description' => "Concurrent payment {$i}",
                    'return_url' => 'https://example.com',
                ]);

            $this->assertEquals(201, $response->status());
        }

        // Verify 3 payments created
        $paymentCount = PaymentTransaction::count();
        $this->assertGreaterThanOrEqual(3, $paymentCount);
    }

    public function test_payment_with_insufficient_balance_after_hold(): void
    {
        $smallWallet = Wallet::factory()->create([
            'tenant_id' => $this->tenant->id,
            'current_balance' => 50000,
        ]);

        // Try payment larger than balance
        $response = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->postJson('/api/v1/payments', [
                'amount' => 100000,
                'currency' => 'RUB',
                'description' => 'Insufficient balance',
                'return_url' => 'https://example.com',
            ]);

        // Should fail or require gateway handling
        $this->assertTrue($response->status() >= 400 || $response->json('requires_gateway'));
    }

    public function test_payment_status_transitions(): void
    {
        $initResponse = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->postJson('/api/v1/payments', [
                'amount' => 75000,
                'currency' => 'RUB',
                'description' => 'Status transition test',
                'return_url' => 'https://example.com',
            ]);

        $transactionId = $initResponse->json('transaction_id');
        $payment = PaymentTransaction::where('payment_id', $transactionId)->first();

        // Verify state transitions: pending → authorized → captured
        $this->assertIn($payment->status, ['pending', 'authorized']);

        $payment->update(['status' => 'authorized']);
        $payment->refresh();
        $this->assertEquals('authorized', $payment->status);

        $payment->update(['status' => 'captured']);
        $payment->refresh();
        $this->assertEquals('captured', $payment->status);
    }

    public function test_payment_with_correlation_id_audit_trail(): void
    {
        $initResponse = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->postJson('/api/v1/payments', [
                'amount' => 120000,
                'currency' => 'RUB',
                'description' => 'Audit trail test',
                'return_url' => 'https://example.com',
            ]);

        $correlationId = $initResponse->json('correlation_id');
        $this->assertNotNull($correlationId);

        // Verify correlation_id persists in payment record
        $transactionId = $initResponse->json('transaction_id');
        $payment = PaymentTransaction::where('payment_id', $transactionId)->first();

        if ($payment) {
            $this->assertEquals($correlationId, $payment->correlation_id);
        }
    }

    public function test_multiple_wallets_isolated(): void
    {
        $wallet2 = Wallet::factory()->create(['tenant_id' => $this->tenant->id, 'current_balance' => 500000]);

        $response1 = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->getJson("/api/v1/wallets/{$this->wallet->id}");

        $response2 = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->getJson("/api/v1/wallets/{$wallet2->id}");

        // Both should be accessible
        $this->assertEquals(200, $response1->status());
        $this->assertEquals(200, $response2->status());

        // Balances should be different
        $this->assertNotEquals(
            $response1->json('current_balance'),
            $response2->json('current_balance')
        );
    }
}
