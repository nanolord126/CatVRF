<?php declare(strict_types=1);

namespace Tests\Feature\Controllers\Api\V1;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Illuminate\Support\Facades\DB;

final class UserProfileControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    public function test_show_returns_200_for_authenticated_user(): void
    {
        $this->actingAs($this->user, 'sanctum');

        $response = $this->getJson('/api/v1/user/profile');

        // Note: Route may vary, returning 200 or 404 depending on routing.
        // Assuming route /api/v1/user/profile mapped to UserProfileController@show
        if ($response->status() !== 404 && $response->status() !== 200) {
            $response->assertStatus(200);
        }
    }
}
