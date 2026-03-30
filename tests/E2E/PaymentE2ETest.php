<?php declare(strict_types=1);

namespace Tests\E2E;

use App\Models\Tenant;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentE2ETest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Tenant $tenant;
    private Wallet $wallet;
    private string $token;

    protected function setUp(): void
    {
        parent::setUp();

        // Create user
        $this->user = User::factory()->create();

        // Create tenant
        $this->tenant = Tenant::factory()->create([
            'owner_id' => $this->user->id,
        ]);

        // Create wallet
        $this->wallet = Wallet::factory()->create([
            'tenant_id' => $this->tenant->id,
            'current_balance' => 100000, // 1000 руб
        ]);

        // Create token
        $this->token = $this->user->createToken('test-token')->plainTextToken;
    }

    /**
     * Test: Get wallet balance
     */
    public function test_get_wallet_balance(): void
    {
        $response = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->getJson("/api/v1/wallets/{$this->wallet->id}");

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => [
                'id',
                'tenant_id',
                'current_balance',
                'hold_amount',
                'available_balance',
            ],
            'correlation_id',
        ]);

        $this->assertEquals(100000, $response->json('data.current_balance'));
    }

    /**
     * Test: Deposit to wallet
     */
    public function test_deposit_to_wallet(): void
    {
        $balanceBefore = $this->wallet->current_balance;

        $response = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->postJson("/api/v1/wallets/{$this->wallet->id}/deposit", [
                'amount' => 50000,
                'reason' => 'Test deposit',
            ]);

        $response->assertStatus(201);
        $response->assertJsonStructure([
            'data' => [
                'transaction_id',
                'amount',
                'balance_after',
            ],
            'correlation_id',
        ]);

        // Verify balance updated
        $this->wallet->refresh();
        $this->assertEquals($balanceBefore + 50000, $this->wallet->current_balance);
    }

    /**
     * Test: Withdraw from wallet
     */
    public function test_withdraw_from_wallet(): void
    {
        $balanceBefore = $this->wallet->current_balance;

        $response = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->postJson("/api/v1/wallets/{$this->wallet->id}/withdraw", [
                'amount' => 25000,
                'reason' => 'Test withdrawal',
            ]);

        $response->assertStatus(201);
        
        // Verify balance updated
        $this->wallet->refresh();
        $this->assertEquals($balanceBefore - 25000, $this->wallet->current_balance);
    }

    /**
     * Test: Withdraw more than balance (should fail)
     */
    public function test_withdraw_insufficient_balance(): void
    {
        $response = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->postJson("/api/v1/wallets/{$this->wallet->id}/withdraw", [
                'amount' => 500000, // 5000 руб, но у нас только 1000
                'reason' => 'Test insufficient',
            ]);

        // Should fail or return error
        $this->assertTrue(
            $response->status() >= 400,
            'Should fail with insufficient balance'
        );
    }

    /**
     * Test: Unauthorized access to another tenant's wallet
     */
    public function test_unauthorized_wallet_access(): void
    {
        $otherUser = User::factory()->create();
        $otherTenant = Tenant::factory()->create(['owner_id' => $otherUser->id]);
        $otherWallet = Wallet::factory()->create(['tenant_id' => $otherTenant->id]);

        $response = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->postJson("/api/v1/wallets/{$otherWallet->id}/deposit", [
                'amount' => 10000,
            ]);

        $response->assertStatus(403);
    }

    /**
     * Test: Multiple transactions in sequence
     */
    public function test_multiple_transactions_sequence(): void
    {
        $startBalance = $this->wallet->current_balance;

        // Deposit 50000
        $this->withHeader('Authorization', "Bearer {$this->token}")
            ->postJson("/api/v1/wallets/{$this->wallet->id}/deposit", [
                'amount' => 50000,
            ])->assertStatus(201);

        // Withdraw 30000
        $this->withHeader('Authorization', "Bearer {$this->token}")
            ->postJson("/api/v1/wallets/{$this->wallet->id}/withdraw", [
                'amount' => 30000,
            ])->assertStatus(201);

        // Deposit 20000
        $this->withHeader('Authorization', "Bearer {$this->token}")
            ->postJson("/api/v1/wallets/{$this->wallet->id}/deposit", [
                'amount' => 20000,
            ])->assertStatus(201);

        // Final balance should be: 100000 + 50000 - 30000 + 20000 = 140000
        $this->wallet->refresh();
        $expectedBalance = $startBalance + 50000 - 30000 + 20000;
        $this->assertEquals($expectedBalance, $this->wallet->current_balance);
    }

    /**
     * Test: Wallet transactions audit log
     */
    public function test_wallet_transactions_logged(): void
    {
        // Deposit
        $this->withHeader('Authorization', "Bearer {$this->token}")
            ->postJson("/api/v1/wallets/{$this->wallet->id}/deposit", [
                'amount' => 50000,
            ]);

        // Check that transaction was created
        $this->assertDatabaseHas('balance_transactions', [
            'wallet_id' => $this->wallet->id,
            'type' => 'deposit',
            'amount' => 50000,
        ]);
    }

    /**
     * Test: Payment initialization (future payment flow)
     */
    public function test_payment_initialization(): void
    {
        $response = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->postJson('/api/v1/payments', [
                'amount' => 100000,
                'currency' => 'RUB',
                'description' => 'Test payment',
                'order_id' => 12345,
            ]);

        // Should return 201 or 200 depending on implementation
        $this->assertTrue($response->status() >= 200 && $response->status() < 400);
        
        if ($response->status() < 300) {
            $response->assertJsonStructure([
                'data' => [
                    'payment_id',
                    'status',
                ],
                'correlation_id',
            ]);
        }
    }
}
