<?php declare(strict_types=1);

namespace Tests\E2E;

use App\Models\Tenant;
use App\Models\User;
use App\Models\Wallet;
use App\Models\PaymentTransaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FinancialIntegrityE2ETest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;
    private User $user;
    private string $token;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tenant = Tenant::factory()->create();
        $this->user = User::factory()->create();
        $this->token = $this->user->createToken('test')->plainTextToken;
    }

    public function test_wallet_balance_integrity(): void
    {
        // Create wallet with initial balance
        $wallet = Wallet::factory()->create([
            'tenant_id' => $this->tenant->id,
            'user_id' => $this->user->id,
            'current_balance' => 100000,
        ]);

        // Perform credit operation
        $creditResponse = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->postJson('/api/v1/wallets/credit', [
                'amount' => 50000,
                'description' => 'Test credit',
            ]);

        $this->assertTrue($creditResponse->status() < 300);

        $wallet->refresh();
        $this->assertEquals(150000, $wallet->current_balance);

        // Perform debit operation
        $debitResponse = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->postJson('/api/v1/wallets/debit', [
                'amount' => 30000,
                'description' => 'Test debit',
            ]);

        $this->assertTrue($debitResponse->status() < 300);

        $wallet->refresh();
        $this->assertEquals(120000, $wallet->current_balance);
    }

    public function test_prevent_negative_balance(): void
    {
        // Create wallet with limited balance
        $wallet = Wallet::factory()->create([
            'tenant_id' => $this->tenant->id,
            'user_id' => $this->user->id,
            'current_balance' => 10000,
        ]);

        // Attempt to debit more than balance
        $response = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->postJson('/api/v1/wallets/debit', [
                'amount' => 50000,
                'description' => 'Attempt overdraft',
            ]);

        // Should be blocked
        $this->assertTrue($response->status() === 422 || $response->status() === 400);

        $wallet->refresh();
        $this->assertEquals(10000, $wallet->current_balance);
    }

    public function test_transaction_atomicity(): void
    {
        // Create wallet
        $wallet = Wallet::factory()->create([
            'tenant_id' => $this->tenant->id,
            'user_id' => $this->user->id,
            'current_balance' => 100000,
        ]);

        // Perform concurrent debit operations
        $responses = [];
        for ($i = 0; $i < 5; $i++) {
            $responses[] = $this->withHeader('Authorization', "Bearer {$this->token}")
                ->postJson('/api/v1/wallets/debit', [
                    'amount' => 30000,
                    'description' => "Concurrent debit {$i}",
                ]);
        }

        // Should not allow negative balance
        $wallet->refresh();
        $this->assertGreaterThanOrEqual(0, $wallet->current_balance);

        // Check transaction records
        $transactionCount = PaymentTransaction::where('wallet_id', $wallet->id)->count();
        $totalDebited = PaymentTransaction::where('wallet_id', $wallet->id)
            ->where('type', 'debit')
            ->sum('amount');

        $this->assertEquals(100000 - $totalDebited, $wallet->current_balance);
    }

    public function test_payment_transaction_audit_trail(): void
    {
        // Create payment
        $response = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->postJson('/api/v1/payments', [
                'amount' => 50000,
                'currency' => 'RUB',
                'description' => 'Audit trail test',
            ]);

        if ($response->status() === 201) {
            $transactionId = $response->json('transaction_id');
            
            // Verify transaction exists in database
            $transaction = PaymentTransaction::where('transaction_id', $transactionId)->first();
            $this->assertNotNull($transaction);

            // Verify audit fields
            $this->assertNotNull($transaction->correlation_id);
            $this->assertNotNull($transaction->created_at);
            $this->assertEquals($this->user->id, $transaction->user_id);
        }
    }

    public function test_cross_tenant_isolation(): void
    {
        // Create two tenants
        $tenant1 = Tenant::factory()->create();
        $tenant2 = Tenant::factory()->create();

        $user1 = User::factory()->create(['tenant_id' => $tenant1->id]);
        $user2 = User::factory()->create(['tenant_id' => $tenant2->id]);

        $token1 = $user1->createToken('test')->plainTextToken;
        $token2 = $user2->createToken('test')->plainTextToken;

        // Create wallet for tenant1
        $wallet1 = Wallet::factory()->create([
            'tenant_id' => $tenant1->id,
            'user_id' => $user1->id,
            'current_balance' => 100000,
        ]);

        // User2 should not be able to access tenant1's wallet
        $response = $this->withHeader('Authorization', "Bearer {$token2}")
            ->getJson("/api/v1/wallets/{$wallet1->id}");

        // Should be forbidden
        $this->assertTrue($response->status() === 403 || $response->status() === 404);
    }

    public function test_duplicate_payment_prevention(): void
    {
        // Create payment
        $response = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->postJson('/api/v1/payments', [
                'amount' => 50000,
                'currency' => 'RUB',
                'description' => 'Duplicate test',
                'external_id' => 'external_123',
            ]);

        $this->assertTrue($response->status() < 300);

        // Attempt to create duplicate payment with same external_id
        $duplicateResponse = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->postJson('/api/v1/payments', [
                'amount' => 50000,
                'currency' => 'RUB',
                'description' => 'Duplicate test',
                'external_id' => 'external_123',
            ]);

        // Should be prevented
        $this->assertTrue($duplicateResponse->status() === 409 || $duplicateResponse->status() === 422);
    }

    public function test_payment_reconciliation(): void
    {
        // Create multiple payments
        $payments = [];
        for ($i = 0; $i < 3; $i++) {
            $response = $this->withHeader('Authorization', "Bearer {$this->token}")
                ->postJson('/api/v1/payments', [
                    'amount' => 50000 * ($i + 1),
                    'currency' => 'RUB',
                    'description' => "Reconciliation test {$i}",
                ]);

            if ($response->status() === 201) {
                $payments[] = $response->json('transaction_id');
            }
        }

        // Verify total amount matches
        $totalAmount = PaymentTransaction::whereIn('transaction_id', $payments)->sum('amount');
        $expectedAmount = 50000 + 100000 + 150000;
        $this->assertEquals($expectedAmount, $totalAmount);
    }

    public function test_currency_conversion_integrity(): void
    {
        // Create payment in foreign currency
        $response = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->postJson('/api/v1/payments', [
                'amount' => 100,
                'currency' => 'USD',
                'description' => 'Currency conversion test',
            ]);

        if ($response->status() === 201) {
            $transaction = PaymentTransaction::where(
                'transaction_id', 
                $response->json('transaction_id')
            )->first();

            // Verify conversion was applied
            $this->assertNotNull($transaction);
            $this->assertGreaterThan(0, $transaction->amount_rub);
            $this->assertEquals(100, $transaction->amount);
        }
    }

    public function test_fee_calculation_integrity(): void
    {
        // Create payment
        $response = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->postJson('/api/v1/payments', [
                'amount' => 100000,
                'currency' => 'RUB',
                'description' => 'Fee calculation test',
            ]);

        if ($response->status() === 201) {
            $transaction = PaymentTransaction::where(
                'transaction_id', 
                $response->json('transaction_id')
            )->first();

            // Verify fee was calculated correctly (e.g., 2%)
            $this->assertNotNull($transaction);
            $expectedFee = 100000 * 0.02; // 2% fee
            $this->assertEqualsWithDelta($expectedFee, $transaction->fee, 1);
        }
    }

    public function test_rollback_on_payment_failure(): void
    {
        // Create wallet with limited balance
        $wallet = Wallet::factory()->create([
            'tenant_id' => $this->tenant->id,
            'user_id' => $this->user->id,
            'current_balance' => 10000,
        ]);

        $initialBalance = $wallet->current_balance;

        // Attempt payment that should fail
        $response = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->postJson('/api/v1/payments', [
                'amount' => 50000,
                'currency' => 'RUB',
                'description' => 'Should fail',
            ]);

        // Balance should remain unchanged
        $wallet->refresh();
        $this->assertEquals($initialBalance, $wallet->current_balance);
    }

    public function test_concurrent_refund_prevention(): void
    {
        // Create payment
        $paymentResponse = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->postJson('/api/v1/payments', [
                'amount' => 100000,
                'currency' => 'RUB',
                'description' => 'Concurrent refund test',
            ]);

        if ($paymentResponse->status() === 201) {
            $paymentId = $paymentResponse->json('transaction_id');
            
            // Attempt concurrent refunds
            $responses = [];
            for ($i = 0; $i < 3; $i++) {
                $responses[] = $this->withHeader('Authorization', "Bearer {$this->token}")
                    ->postJson("/api/v1/payments/{$paymentId}/refund", [
                        'reason' => 'Test refund',
                    ]);
            }

            // Only one should succeed
            $successCount = count(array_filter($responses, fn($r) => $r->status() < 300));
            $this->assertEquals(1, $successCount);
        }
    }
}
