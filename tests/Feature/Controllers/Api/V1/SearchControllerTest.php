<?php declare(strict_types=1);

namespace Tests\Feature\Controllers\Api\V1;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tests for Api/V1/SearchController
 * Routes:
 *   GET /api/v1/search             — full-text search across verticals
 *   GET /api/v1/search/suggestions — autocomplete suggestions
 */
final class SearchControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    // ── validation: q param ──────────────────────────────────────────

    public function test_search_returns_422_when_q_is_missing(): void
    {
        $response = $this->getJson('/api/v1/search');

        $response->assertStatus(422);
    }

    public function test_search_returns_422_when_q_is_too_short(): void
    {
        $response = $this->getJson('/api/v1/search?q=a');

        $response->assertStatus(422);
    }

    public function test_search_returns_422_for_invalid_vertical(): void
    {
        $response = $this->getJson('/api/v1/search?q=test&vertical=nonexistent_vertical');

        $response->assertStatus(422);
    }

    public function test_search_returns_422_for_invalid_sort(): void
    {
        $response = $this->getJson('/api/v1/search?q=test&sort=invalid_sort');

        $response->assertStatus(422);
    }

    public function test_search_returns_422_for_negative_min_price(): void
    {
        $response = $this->getJson('/api/v1/search?q=test&min_price=-1');

        $response->assertStatus(422);
    }

    // ── successful search ────────────────────────────────────────────

    public function test_search_returns_200_for_valid_query(): void
    {
        $response = $this->getJson('/api/v1/search?q=beauty');

        $response->assertStatus(200);
    }

    public function test_search_response_contains_success_flag(): void
    {
        $response = $this->getJson('/api/v1/search?q=beauty');

        $response->assertJson(['success' => true]);
    }

    public function test_search_response_contains_data(): void
    {
        $response = $this->getJson('/api/v1/search?q=beauty');

        $response->assertJsonStructure(['data']);
    }

    public function test_search_data_contains_total(): void
    {
        $response = $this->getJson('/api/v1/search?q=beauty');

        $response->assertJsonStructure(['data' => ['total']]);
    }

    public function test_search_data_contains_results(): void
    {
        $response = $this->getJson('/api/v1/search?q=beauty');

        $response->assertJsonStructure(['data' => ['results']]);
    }

    public function test_search_data_contains_per_page(): void
    {
        $response = $this->getJson('/api/v1/search?q=beauty');

        $response->assertJsonStructure(['data' => ['per_page']]);
    }

    public function test_search_data_contains_current_page(): void
    {
        $response = $this->getJson('/api/v1/search?q=beauty');

        $response->assertJsonStructure(['data' => ['current_page']]);
    }

    public function test_search_response_contains_correlation_id(): void
    {
        $response = $this->getJson('/api/v1/search?q=beauty');

        $response->assertJsonStructure(['correlation_id']);
    }

    public function test_search_does_not_require_authentication(): void
    {
        // No actingAs — should still work (no auth middleware on search)
        $response = $this->getJson('/api/v1/search?q=salon');

        $response->assertStatus(200);
    }

    public function test_search_accepts_valid_vertical_param(): void
    {
        $response = $this->getJson('/api/v1/search?q=salon&vertical=beauty');

        $response->assertStatus(200);
    }

    public function test_search_accepts_sort_by_price_asc(): void
    {
        $response = $this->getJson('/api/v1/search?q=salon&sort=price_asc');

        $response->assertStatus(200);
    }

    public function test_search_accepts_sort_by_rating(): void
    {
        $response = $this->getJson('/api/v1/search?q=salon&sort=rating');

        $response->assertStatus(200);
    }

    public function test_search_accepts_sort_by_relevance(): void
    {
        $response = $this->getJson('/api/v1/search?q=salon&sort=relevance');

        $response->assertStatus(200);
    }

    public function test_search_respects_x_correlation_id_header(): void
    {
        $correlationId = 'test-search-correlation-id';

        $response = $this->getJson('/api/v1/search?q=beauty', [
            'X-Correlation-ID' => $correlationId,
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('correlation_id', $correlationId);
    }

    // ── suggestions ───────────────────────────────────────────────────

    public function test_suggestions_returns_422_when_q_is_missing(): void
    {
        $response = $this->getJson('/api/v1/search/suggestions');

        $response->assertStatus(422);
    }

    public function test_suggestions_returns_200_for_valid_query(): void
    {
        $response = $this->getJson('/api/v1/search/suggestions?q=bea');

        $response->assertStatus(200);
    }

    public function test_suggestions_response_contains_success_flag(): void
    {
        $response = $this->getJson('/api/v1/search/suggestions?q=bea');

        $response->assertJson(['success' => true]);
    }

    public function test_suggestions_response_contains_data(): void
    {
        $response = $this->getJson('/api/v1/search/suggestions?q=bea');

        $response->assertJsonStructure(['data']);
    }
}
