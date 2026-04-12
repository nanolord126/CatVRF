<?php declare(strict_types=1);

namespace Tests\Feature\Controllers\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tests for Auth\TokenController
 * Covers: create (POST token), refresh (authenticated).
 * Routes assumed under /auth/token or /api/auth/token — adjust if needed.
 */
final class TokenControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create([
            'email'    => 'token-test@example.com',
            'password' => bcrypt('password123'),
        ]);
    }

    public function test_create_returns_201_with_valid_credentials(): void
    {
        $response = $this->postJson('/auth/token', [
            'email'    => 'token-test@example.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(201);
    }

    public function test_create_response_contains_token_key(): void
    {
        $response = $this->postJson('/auth/token', [
            'email'    => 'token-test@example.com',
            'password' => 'password123',
        ]);

        $response->assertJsonStructure(['token']);
    }

    public function test_create_response_type_is_bearer(): void
    {
        $response = $this->postJson('/auth/token', [
            'email'    => 'token-test@example.com',
            'password' => 'password123',
        ]);

        $response->assertJson(['type' => 'Bearer']);
    }

    public function test_create_response_contains_expires_in(): void
    {
        $response = $this->postJson('/auth/token', [
            'email'    => 'token-test@example.com',
            'password' => 'password123',
        ]);

        $response->assertJsonStructure(['expires_in']);
    }

    public function test_create_response_contains_correlation_id(): void
    {
        $response = $this->postJson('/auth/token', [
            'email'    => 'token-test@example.com',
            'password' => 'password123',
        ]);

        $response->assertJsonStructure(['correlation_id']);
    }

    public function test_create_returns_401_with_wrong_password(): void
    {
        $response = $this->postJson('/auth/token', [
            'email'    => 'token-test@example.com',
            'password' => 'wrong_password',
        ]);

        $response->assertStatus(401);
    }

    public function test_create_returns_401_for_unknown_email(): void
    {
        $response = $this->postJson('/auth/token', [
            'email'    => 'ghost@example.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(401);
    }

    public function test_create_returns_422_when_email_missing(): void
    {
        $response = $this->postJson('/auth/token', [
            'password' => 'password123',
        ]);

        $response->assertStatus(422);
    }

    public function test_create_returns_422_when_password_missing(): void
    {
        $response = $this->postJson('/auth/token', [
            'email' => 'token-test@example.com',
        ]);

        $response->assertStatus(422);
    }

    public function test_create_401_contains_error_key(): void
    {
        $response = $this->postJson('/auth/token', [
            'email'    => 'token-test@example.com',
            'password' => 'wrong',
        ]);

        $response->assertJsonStructure(['error']);
    }

    public function test_refresh_returns_200_when_authenticated(): void
    {
        $this->actingAs($this->user, 'sanctum');

        $response = $this->postJson('/auth/token/refresh');

        $response->assertStatus(200);
    }

    public function test_refresh_response_contains_new_token(): void
    {
        $this->actingAs($this->user, 'sanctum');

        $response = $this->postJson('/auth/token/refresh');

        $response->assertJsonStructure(['token']);
    }

    public function test_refresh_response_type_is_bearer(): void
    {
        $this->actingAs($this->user, 'sanctum');

        $response = $this->postJson('/auth/token/refresh');

        $response->assertJson(['type' => 'Bearer']);
    }

    public function test_refresh_response_contains_correlation_id(): void
    {
        $this->actingAs($this->user, 'sanctum');

        $response = $this->postJson('/auth/token/refresh');

        $response->assertJsonStructure(['correlation_id']);
    }

    public function test_refresh_returns_401_for_unauthenticated(): void
    {
        $response = $this->postJson('/auth/token/refresh');

        $response->assertStatus(401);
    }
}
