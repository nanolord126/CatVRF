<?php declare(strict_types=1);

namespace Tests\Feature\Controllers\Api\V1;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tests for Api/V1/WalletController
 * Routes:
 *   GET  /api/v1/wallet         — current balance
 *   GET  /api/v1/wallet/history — transaction history
 *   POST /api/v1/wallet/deposit — top-up balance
 */
final class WalletControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    // ── show ──────────────────────────────────────────────────────────

    public function test_show_returns_200_for_authenticated_user(): void
    {
        $this->actingAs($this->user, 'sanctum');

        $response = $this->getJson('/api/v1/wallet');

        $response->assertStatus(200);
    }

    public function test_show_response_contains_success_flag(): void
    {
        $this->actingAs($this->user, 'sanctum');

        $response = $this->getJson('/api/v1/wallet');

        $response->assertJson(['success' => true]);
    }

    public function test_show_response_contains_data(): void
    {
        $this->actingAs($this->user, 'sanctum');

        $response = $this->getJson('/api/v1/wallet');

        $response->assertJsonStructure(['data']);
    }

    public function test_show_data_contains_balance(): void
    {
        $this->actingAs($this->user, 'sanctum');

        $response = $this->getJson('/api/v1/wallet');

        $response->assertJsonStructure(['data' => ['balance']]);
    }

    public function test_show_data_contains_available_balance(): void
    {
        $this->actingAs($this->user, 'sanctum');

        $response = $this->getJson('/api/v1/wallet');

        $response->assertJsonStructure(['data' => ['available_balance']]);
    }

    public function test_show_data_contains_currency(): void
    {
        $this->actingAs($this->user, 'sanctum');

        $response = $this->getJson('/api/v1/wallet');

        $response->assertJson(['data' => ['currency' => 'RUB']]);
    }

    public function test_show_response_contains_correlation_id(): void
    {
        $this->actingAs($this->user, 'sanctum');

        $response = $this->getJson('/api/v1/wallet');

        $response->assertJsonStructure(['correlation_id']);
    }

    public function test_show_returns_401_for_unauthenticated(): void
    {
        $response = $this->getJson('/api/v1/wallet');

        $response->assertStatus(401);
    }

    // ── history ───────────────────────────────────────────────────────

    public function test_history_returns_200_for_authenticated_user(): void
    {
        $this->actingAs($this->user, 'sanctum');

        $response = $this->getJson('/api/v1/wallet/history');

        $response->assertStatus(200);
    }

    public function test_history_response_contains_success_flag(): void
    {
        $this->actingAs($this->user, 'sanctum');

        $response = $this->getJson('/api/v1/wallet/history');

        $response->assertJson(['success' => true]);
    }

    public function test_history_response_contains_data(): void
    {
        $this->actingAs($this->user, 'sanctum');

        $response = $this->getJson('/api/v1/wallet/history');

        $response->assertJsonStructure(['data']);
    }

    public function test_history_response_contains_meta(): void
    {
        $this->actingAs($this->user, 'sanctum');

        $response = $this->getJson('/api/v1/wallet/history');

        $response->assertJsonStructure(['meta' => ['current_page', 'last_page', 'per_page', 'total']]);
    }

    public function test_history_response_contains_correlation_id(): void
    {
        $this->actingAs($this->user, 'sanctum');

        $response = $this->getJson('/api/v1/wallet/history');

        $response->assertJsonStructure(['correlation_id']);
    }

    public function test_history_returns_401_for_unauthenticated(): void
    {
        $response = $this->getJson('/api/v1/wallet/history');

        $response->assertStatus(401);
    }

    public function test_history_respects_per_page_param(): void
    {
        $this->actingAs($this->user, 'sanctum');

        $response = $this->getJson('/api/v1/wallet/history?per_page=5');

        $response->assertStatus(200);
        $response->assertJsonPath('meta.per_page', 5);
    }

    public function test_history_caps_per_page_at_50(): void
    {
        $this->actingAs($this->user, 'sanctum');

        $response = $this->getJson('/api/v1/wallet/history?per_page=200');

        $response->assertStatus(200);
        $data = $response->json('meta.per_page');
        $this->assertLessThanOrEqual(50, $data);
    }

    // ── deposit ───────────────────────────────────────────────────────

    public function test_deposit_returns_401_for_unauthenticated(): void
    {
        $response = $this->postJson('/api/v1/wallet/deposit', ['amount' => 1000]);

        $response->assertStatus(401);
    }
}
