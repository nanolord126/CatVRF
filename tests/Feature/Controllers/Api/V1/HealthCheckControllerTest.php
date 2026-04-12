<?php declare(strict_types=1);

namespace Tests\Feature\Controllers\Api\V1;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * Tests for GET /health
 * No authentication required.
 * Checks DB, Redis, Cache, Sanctum, rate_limiter, idempotency, webhook_validator.
 */
final class HealthCheckControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_health_endpoint_returns_200(): void
    {
        $response = $this->getJson('/health');

        $response->assertStatus(200);
    }

    public function test_health_response_has_status_key(): void
    {
        $response = $this->getJson('/health');

        $response->assertJsonStructure(['status']);
    }

    public function test_health_response_has_components_key(): void
    {
        $response = $this->getJson('/health');

        $response->assertJsonStructure(['components']);
    }

    public function test_health_response_has_environment_key(): void
    {
        $response = $this->getJson('/health');

        $response->assertJsonStructure(['environment']);
    }

    public function test_health_response_has_version_key(): void
    {
        $response = $this->getJson('/health');

        $response->assertJsonStructure(['version']);
    }

    public function test_health_response_has_timestamp_key(): void
    {
        $response = $this->getJson('/health');

        $response->assertJsonStructure(['timestamp']);
    }

    public function test_health_status_is_ok_or_degraded(): void
    {
        $response = $this->getJson('/health');

        $data = $response->json();
        $this->assertContains($data['status'], ['ok', 'degraded']);
    }

    public function test_health_version_matches_expected(): void
    {
        $response = $this->getJson('/health');

        $response->assertJson(['version' => '1.0.0']);
    }

    public function test_health_components_contains_database(): void
    {
        $response = $this->getJson('/health');

        $response->assertJsonStructure(['components' => ['database']]);
    }

    public function test_health_components_contains_cache(): void
    {
        $response = $this->getJson('/health');

        $response->assertJsonStructure(['components' => ['cache']]);
    }

    public function test_health_components_contains_sanctum(): void
    {
        $response = $this->getJson('/health');

        $response->assertJsonStructure(['components' => ['sanctum']]);
    }

    public function test_health_does_not_require_auth(): void
    {
        // No actingAs — anonymous request should work
        $response = $this->getJson('/health');

        $response->assertStatus(200);
    }

    public function test_health_accepts_get_only(): void
    {
        $this->postJson('/health')->assertStatus(405);
    }

    public function test_health_environment_is_string(): void
    {
        $response = $this->getJson('/health');

        $data = $response->json();
        $this->assertIsString($data['environment']);
    }
}
