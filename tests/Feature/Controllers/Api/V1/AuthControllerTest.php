<?php declare(strict_types=1);

namespace Tests\Feature\Controllers\Api\V1;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tests for Api/V1/AuthController
 * Covers: store (POST /api/v1/auth/tokens),
 *         refresh, destroy, index.
 */
final class AuthControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create([
            'email'    => 'auth-test@example.com',
            'password' => bcrypt('secret123'),
        ]);
    }

    public function test_store_returns_201_with_valid_credentials(): void
    {
        $response = $this->postJson('/api/v1/auth/tokens', [
            'email'    => 'auth-test@example.com',
            'password' => 'secret123',
            'name'     => 'TestToken',
        ]);

        $response->assertStatus(201);
    }

    public function test_store_response_contains_token(): void
    {
        $response = $this->postJson('/api/v1/auth/tokens', [
            'email'    => 'auth-test@example.com',
            'password' => 'secret123',
            'name'     => 'TestToken',
        ]);

        $response->assertJsonStructure(['token']);
    }

    public function test_store_response_contains_bearer_type(): void
    {
        $response = $this->postJson('/api/v1/auth/tokens', [
            'email'    => 'auth-test@example.com',
            'password' => 'secret123',
        ]);

        $response->assertJson(['type' => 'Bearer']);
    }

    public function test_store_response_contains_correlation_id(): void
    {
        $response = $this->postJson('/api/v1/auth/tokens', [
            'email'    => 'auth-test@example.com',
            'password' => 'secret123',
        ]);

        $response->assertJsonStructure(['correlation_id']);
    }

    public function test_store_returns_401_with_wrong_password(): void
    {
        $response = $this->postJson('/api/v1/auth/tokens', [
            'email'    => 'auth-test@example.com',
            'password' => 'wrongpassword',
        ]);

        $response->assertStatus(401);
    }

    public function test_store_returns_401_with_nonexistent_email(): void
    {
        $response = $this->postJson('/api/v1/auth/tokens', [
            'email'    => 'nobody@example.com',
            'password' => 'secret123',
        ]);

        $response->assertStatus(401);
    }

    public function test_store_401_response_contains_correlation_id(): void
    {
        $response = $this->postJson('/api/v1/auth/tokens', [
            'email'    => 'auth-test@example.com',
            'password' => 'wrong',
        ]);

        $response->assertJsonStructure(['correlation_id']);
    }

    public function test_store_respects_x_correlation_id_header(): void
    {
        $correlationId = 'test-correlation-id-12345';

        $response = $this->withHeader('X-Correlation-ID', $correlationId)
            ->postJson('/api/v1/auth/tokens', [
                'email'    => 'auth-test@example.com',
                'password' => 'secret123',
            ]);

        $response->assertJson(['correlation_id' => $correlationId]);
    }

    public function test_refresh_returns_200_for_authenticated_user(): void
    {
        $this->actingAs($this->user, 'sanctum');

        $response = $this->postJson('/api/v1/auth/tokens/refresh');

        $response->assertStatus(200);
    }

    public function test_refresh_returns_new_token(): void
    {
        $this->actingAs($this->user, 'sanctum');

        $response = $this->postJson('/api/v1/auth/tokens/refresh');

        $response->assertJsonStructure(['token']);
    }

    public function test_refresh_returns_401_for_unauthenticated(): void
    {
        $response = $this->postJson('/api/v1/auth/tokens/refresh');

        $response->assertStatus(401);
    }

    public function test_index_returns_200_for_authenticated_user(): void
    {
        $this->actingAs($this->user, 'sanctum');

        $response = $this->getJson('/api/v1/auth/tokens');

        $response->assertStatus(200);
    }

    public function test_index_returns_401_for_unauthenticated(): void
    {
        $response = $this->getJson('/api/v1/auth/tokens');

        $response->assertStatus(401);
    }

    public function test_destroy_deletes_token_for_authenticated_user(): void
    {
        $token = $this->user->createToken('DeleteMe');
        $tokenId = $token->accessToken->id;

        $this->actingAs($this->user, 'sanctum');

        $response = $this->deleteJson("/api/v1/auth/tokens/{$tokenId}");

        $response->assertStatus(200);
    }

    public function test_destroy_returns_401_for_unauthenticated(): void
    {
        $response = $this->deleteJson('/api/v1/auth/tokens/1');

        $response->assertStatus(401);
    }
}
