<?php declare(strict_types=1);

namespace Tests\Feature\Controllers\Beauty;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class AppointmentControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    public function test_cancel_requires_auth(): void
    {
        $response = $this->postJson('/api/v1/beauty/appointments/fake-uuid/cancel');

        $response->assertStatus(401);
    }

    public function test_reschedule_requires_auth(): void
    {
        $response = $this->postJson('/api/v1/beauty/appointments/fake-uuid/reschedule', [
            'new_start_time' => now()->addDays(2)->toIso8601String(),
        ]);

        $response->assertStatus(401);
    }
}
