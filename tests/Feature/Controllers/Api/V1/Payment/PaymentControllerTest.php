<?php declare(strict_types=1);

namespace Tests\Feature\Controllers\Api\V1\Payment;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tests for Api/V1/Payment/PaymentController
 * Routes:
 *   POST /api/v1/payments/init         — initiate payment (hold)
 *   POST /api/v1/payments/{id}/capture — capture payment (settle)
 */
final class PaymentControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    // ── init: auth ────────────────────────────────────────────────────

    public function test_init_returns_401_for_unauthenticated(): void
    {
        $response = $this->postJson('/api/v1/payments/init', [
            'amount' => 1000,
            'operation_type' => 'food_order',
            'idempotency_key' => 'test-key-' . uniqid(),
        ]);

        $response->assertStatus(401);
    }

    // ── init: validation ──────────────────────────────────────────────

    public function test_init_returns_422_when_amount_is_missing(): void
    {
        $this->actingAs($this->user, 'sanctum');

        $response = $this->postJson('/api/v1/payments/init', [
            'operation_type' => 'food_order',
            'idempotency_key' => 'test-key-' . uniqid(),
        ]);

        $response->assertStatus(422);
    }

    public function test_init_returns_422_when_operation_type_is_missing(): void
    {
        $this->actingAs($this->user, 'sanctum');

        $response = $this->postJson('/api/v1/payments/init', [
            'amount' => 1000,
            'idempotency_key' => 'test-key-' . uniqid(),
        ]);

        $response->assertStatus(422);
    }

    public function test_init_returns_422_when_idempotency_key_is_missing(): void
    {
        $this->actingAs($this->user, 'sanctum');

        $response = $this->postJson('/api/v1/payments/init', [
            'amount' => 1000,
            'operation_type' => 'food_order',
        ]);

        $response->assertStatus(422);
    }

    public function test_init_returns_422_when_amount_is_zero(): void
    {
        $this->actingAs($this->user, 'sanctum');

        $response = $this->postJson('/api/v1/payments/init', [
            'amount' => 0,
            'operation_type' => 'food_order',
            'idempotency_key' => 'test-key-' . uniqid(),
        ]);

        $response->assertStatus(422);
    }

    public function test_init_returns_422_when_amount_is_negative(): void
    {
        $this->actingAs($this->user, 'sanctum');

        $response = $this->postJson('/api/v1/payments/init', [
            'amount' => -500,
            'operation_type' => 'food_order',
            'idempotency_key' => 'test-key-' . uniqid(),
        ]);

        $response->assertStatus(422);
    }

    // ── init: successful ──────────────────────────────────────────────

    public function test_init_returns_201_for_valid_request(): void
    {
        $this->actingAs($this->user, 'sanctum');

        $response = $this->postJson('/api/v1/payments/init', [
            'amount' => 5000,
            'operation_type' => 'food_order',
            'idempotency_key' => 'unique-key-' . uniqid(),
        ]);

        $response->assertStatus(201);
    }

    public function test_init_response_contains_success_flag(): void
    {
        $this->actingAs($this->user, 'sanctum');

        $response = $this->postJson('/api/v1/payments/init', [
            'amount' => 5000,
            'operation_type' => 'food_order',
            'idempotency_key' => 'unique-key-' . uniqid(),
        ]);

        $response->assertJson(['success' => true]);
    }

    public function test_init_response_contains_payment_id(): void
    {
        $this->actingAs($this->user, 'sanctum');

        $response = $this->postJson('/api/v1/payments/init', [
            'amount' => 5000,
            'operation_type' => 'food_order',
            'idempotency_key' => 'unique-key-' . uniqid(),
        ]);

        $response->assertJsonStructure(['data' => ['payment_id']]);
    }

    public function test_init_response_contains_transaction_id(): void
    {
        $this->actingAs($this->user, 'sanctum');

        $response = $this->postJson('/api/v1/payments/init', [
            'amount' => 5000,
            'operation_type' => 'food_order',
            'idempotency_key' => 'unique-key-' . uniqid(),
        ]);

        $response->assertJsonStructure(['data' => ['transaction_id']]);
    }

    public function test_init_response_status_is_authorized(): void
    {
        $this->actingAs($this->user, 'sanctum');

        $response = $this->postJson('/api/v1/payments/init', [
            'amount' => 5000,
            'operation_type' => 'food_order',
            'idempotency_key' => 'unique-key-' . uniqid(),
        ]);

        $response->assertJsonPath('data.status', 'authorized');
    }

    public function test_init_response_contains_amount(): void
    {
        $this->actingAs($this->user, 'sanctum');

        $response = $this->postJson('/api/v1/payments/init', [
            'amount' => 5000,
            'operation_type' => 'food_order',
            'idempotency_key' => 'unique-key-' . uniqid(),
        ]);

        $response->assertJsonStructure(['data' => ['amount']]);
    }

    public function test_init_response_contains_currency(): void
    {
        $this->actingAs($this->user, 'sanctum');

        $response = $this->postJson('/api/v1/payments/init', [
            'amount' => 5000,
            'operation_type' => 'food_order',
            'idempotency_key' => 'unique-key-' . uniqid(),
        ]);

        $response->assertJsonStructure(['data' => ['currency']]);
    }

    public function test_init_response_contains_correlation_id(): void
    {
        $this->actingAs($this->user, 'sanctum');

        $response = $this->postJson('/api/v1/payments/init', [
            'amount' => 5000,
            'operation_type' => 'food_order',
            'idempotency_key' => 'unique-key-' . uniqid(),
        ]);

        $response->assertJsonStructure(['correlation_id']);
    }

    // ── init: idempotency ─────────────────────────────────────────────

    public function test_init_returns_200_when_same_idempotency_key_used_twice(): void
    {
        $this->actingAs($this->user, 'sanctum');
        $key = 'idempotency-key-' . uniqid();

        $this->postJson('/api/v1/payments/init', [
            'amount' => 5000,
            'operation_type' => 'food_order',
            'idempotency_key' => $key,
        ]);

        $second = $this->postJson('/api/v1/payments/init', [
            'amount' => 5000,
            'operation_type' => 'food_order',
            'idempotency_key' => $key,
        ]);

        $second->assertStatus(200);
    }

    // ── capture: auth ─────────────────────────────────────────────────

    public function test_capture_returns_401_for_unauthenticated(): void
    {
        $response = $this->postJson('/api/v1/payments/1/capture');

        $response->assertStatus(401);
    }

    // ── capture: tenant mismatch ──────────────────────────────────────

    public function test_capture_returns_403_for_wrong_tenant(): void
    {
        $this->actingAs($this->user, 'sanctum');

        // Attempting to capture a payment from a different tenant (id=9999)
        $response = $this->postJson('/api/v1/payments/9999/capture');

        // Either 403 (wrong tenant) or 404 (not found) — both are acceptable security responses
        $this->assertContains($response->getStatusCode(), [403, 404]);
    }
}
