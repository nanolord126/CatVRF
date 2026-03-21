<?php declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use App\Models\Tenant;
use App\Models\Wallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WalletControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Tenant $tenant;
    private Wallet $wallet;
    private string $token;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tenant = Tenant::factory()->create();
        $this->user = User::factory()->create();
        $this->wallet = Wallet::factory()->create([
            'tenant_id' => $this->tenant->id,
            'current_balance' => 500000,
        ]);
        $this->token = $this->user->createToken('test')->plainTextToken;
    }

    public function test_index_wallets_returns_list(): void
    {
        $response = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->getJson('/api/v1/wallets');

        $response->assertStatus(200);
        $response->assertJsonStructure(['data']);
    }

    public function test_show_wallet_returns_details(): void
    {
        $response = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->getJson("/api/v1/wallets/{$this->wallet->id}");

        $response->assertStatus(200);
        $response->assertJsonPath('current_balance', 500000);
        $response->assertJsonPath('hold_amount', 0);
    }

    public function test_show_wallet_requires_auth(): void
    {
        $response = $this->getJson("/api/v1/wallets/{$this->wallet->id}");
        $response->assertStatus(401);
    }

    public function test_show_nonexistent_wallet(): void
    {
        $response = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->getJson('/api/v1/wallets/999999');

        $response->assertStatus(404);
    }

    public function test_deposit_increases_balance(): void
    {
        $response = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->postJson("/api/v1/wallets/{$this->wallet->id}/deposit", [
                'amount' => 100000,
                'source' => 'card',
            ]);

        $response->assertStatus(200);
        $response->assertJsonPath('balance_after', 600000);

        $this->wallet->refresh();
        $this->assertEquals(600000, $this->wallet->current_balance);
    }

    public function test_deposit_validates_amount(): void
    {
        $response = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->postJson("/api/v1/wallets/{$this->wallet->id}/deposit", [
                'amount' => 0,
                'source' => 'card',
            ]);

        $response->assertStatus(422);
    }

    public function test_withdraw_decreases_balance(): void
    {
        $response = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->postJson("/api/v1/wallets/{$this->wallet->id}/withdraw", [
                'amount' => 100000,
            ]);

        $response->assertStatus(200);
        $response->assertJsonPath('balance_after', 400000);

        $this->wallet->refresh();
        $this->assertEquals(400000, $this->wallet->current_balance);
    }

    public function test_withdraw_insufficient_balance(): void
    {
        $response = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->postJson("/api/v1/wallets/{$this->wallet->id}/withdraw", [
                'amount' => 600000,
            ]);

        $response->assertStatus(422);
    }

    public function test_withdraw_prevents_negative_balance(): void
    {
        $response = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->postJson("/api/v1/wallets/{$this->wallet->id}/withdraw", [
                'amount' => 1000000,
            ]);

        $response->assertStatus(422);
    }

    public function test_deposit_creates_transaction_record(): void
    {
        $this->withHeader('Authorization', "Bearer {$this->token}")
            ->postJson("/api/v1/wallets/{$this->wallet->id}/deposit", [
                'amount' => 50000,
                'source' => 'card',
            ]);

        $transaction = \App\Models\BalanceTransaction::where('wallet_id', $this->wallet->id)
            ->where('type', 'credit')
            ->first();

        $this->assertNotNull($transaction);
        $this->assertEquals(50000, $transaction->amount);
    }

    public function test_multiple_withdrawals_sequential(): void
    {
        $this->withHeader('Authorization', "Bearer {$this->token}")
            ->postJson("/api/v1/wallets/{$this->wallet->id}/withdraw", ['amount' => 100000]);

        $this->wallet->refresh();
        $this->assertEquals(400000, $this->wallet->current_balance);

        $this->withHeader('Authorization', "Bearer {$this->token}")
            ->postJson("/api/v1/wallets/{$this->wallet->id}/withdraw", ['amount' => 100000]);

        $this->wallet->refresh();
        $this->assertEquals(300000, $this->wallet->current_balance);
    }

    public function test_deposit_and_withdraw_mixed(): void
    {
        $this->withHeader('Authorization', "Bearer {$this->token}")
            ->postJson("/api/v1/wallets/{$this->wallet->id}/deposit", [
                'amount' => 250000,
                'source' => 'card',
            ]);

        $this->withHeader('Authorization', "Bearer {$this->token}")
            ->postJson("/api/v1/wallets/{$this->wallet->id}/withdraw", ['amount' => 150000]);

        $this->wallet->refresh();
        $this->assertEquals(600000, $this->wallet->current_balance);
    }

    public function test_wallet_response_includes_correlation_id(): void
    {
        $response = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->postJson("/api/v1/wallets/{$this->wallet->id}/deposit", [
                'amount' => 100000,
                'source' => 'card',
            ]);

        $response->assertJsonStructure(['correlation_id']);
    }

    public function test_unauthorized_cannot_access_wallet(): void
    {
        $otherUser = User::factory()->create();
        $otherToken = $otherUser->createToken('other')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$otherToken}")
            ->getJson("/api/v1/wallets/{$this->wallet->id}");

        // Should either 403 or 404 (not return 200)
        $this->assertGreaterThanOrEqual(400, $response->status());
    }
}
