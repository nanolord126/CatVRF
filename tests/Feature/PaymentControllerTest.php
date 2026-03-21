<?php declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use App\Models\Tenant;
use App\Models\Wallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentControllerTest extends TestCase
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
        $this->wallet = Wallet::factory()->create(['tenant_id' => $this->tenant->id]);
        $this->token = $this->user->createToken('test')->plainTextToken;
    }

    public function test_init_payment_returns_201(): void
    {
        $response = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->postJson('/api/v1/payments', [
                'amount' => 100000,
                'currency' => 'RUB',
                'description' => 'Test payment',
                'return_url' => 'https://example.com/return',
            ]);

        $response->assertStatus(201);
        $response->assertJsonStructure(['transaction_id', 'status', 'payment_url']);
    }

    public function test_init_payment_validates_amount(): void
    {
        $response = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->postJson('/api/v1/payments', [
                'amount' => -100,
                'currency' => 'RUB',
            ]);

        $response->assertStatus(422);
    }

    public function test_get_payment_returns_details(): void
    {
        $paymentResponse = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->postJson('/api/v1/payments', [
                'amount' => 100000,
                'currency' => 'RUB',
                'description' => 'Test',
                'return_url' => 'https://example.com',
            ]);

        $transactionId = $paymentResponse->json('transaction_id');

        $getResponse = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->getJson("/api/v1/payments/{$transactionId}");

        $getResponse->assertStatus(200);
        $getResponse->assertJsonPath('transaction_id', $transactionId);
    }

    public function test_get_payment_requires_auth(): void
    {
        $response = $this->getJson('/api/v1/payments/nonexistent');
        $response->assertStatus(401);
    }

    public function test_refund_payment(): void
    {
        $initResponse = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->postJson('/api/v1/payments', [
                'amount' => 100000,
                'currency' => 'RUB',
                'description' => 'Test',
                'return_url' => 'https://example.com',
            ]);

        $transactionId = $initResponse->json('transaction_id');

        $refundResponse = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->postJson("/api/v1/payments/{$transactionId}/refund", [
                'reason' => 'Customer request',
            ]);

        $refundResponse->assertStatus(200);
        $refundResponse->assertJsonPath('status', 'refunded');
    }

    public function test_refund_nonexistent_payment(): void
    {
        $response = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->postJson('/api/v1/payments/fake-id/refund', [
                'reason' => 'Test',
            ]);

        $response->assertStatus(404);
    }

    public function test_list_payments_pagination(): void
    {
        // Create 3 payments
        for ($i = 0; $i < 3; $i++) {
            $this->withHeader('Authorization', "Bearer {$this->token}")
                ->postJson('/api/v1/payments', [
                    'amount' => 100000 + ($i * 10000),
                    'currency' => 'RUB',
                    'description' => "Payment {$i}",
                    'return_url' => 'https://example.com',
                ]);
        }

        $response = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->getJson('/api/v1/payments?per_page=2');

        $response->assertStatus(200);
        $response->assertJsonStructure(['data', 'meta']);
        $this->assertCount(2, $response->json('data'));
    }

    public function test_payment_includes_correlation_id(): void
    {
        $response = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->postJson('/api/v1/payments', [
                'amount' => 100000,
                'currency' => 'RUB',
                'description' => 'Test',
                'return_url' => 'https://example.com',
            ]);

        $response->assertJsonStructure(['correlation_id']);
        $this->assertNotNull($response->json('correlation_id'));
    }
}
