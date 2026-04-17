<?php declare(strict_types=1);

namespace Tests\Feature\Domains\Education;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Support\Facades\DB;

final class LiveClassApiTest extends TestCase
{
    private User $user;
    private string $token;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->token = $this->user->createToken('test-token')->plainTextToken;
    }

    public function test_create_live_session_unauthorized(): void
    {
        $response = $this->postJson('/api/v1/education/live-classes/1/create-session');
        $response->assertStatus(401);
    }

    public function test_create_live_session_success(): void
    {
        DB::table('education_slots')->insert([
            'id' => 1,
            'uuid' => 'slot-uuid-1',
            'tenant_id' => tenant()->id,
            'teacher_id' => 1,
            'title' => 'Test Slot',
            'start_time' => now()->addHour(),
            'end_time' => now()->addHours(2),
            'duration_minutes' => 60,
            'capacity' => 10,
            'booked_count' => 0,
            'slot_type' => 'webinar',
            'status' => 'available',
        ]);

        $response = $this->withToken($this->token)
            ->postJson('/api/v1/education/live-classes/1/create-session', [], [
                'X-Correlation-ID' => 'test-correlation-123',
            ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'session_id',
                'meeting_id',
                'teacher_token',
                'status',
            ])
            ->assertHeader('X-Correlation-ID', 'test-correlation-123');
    }

    public function test_join_session_success(): void
    {
        DB::table('education_live_sessions')->insert([
            'id' => 'session-1',
            'tenant_id' => tenant()->id,
            'slot_id' => 1,
            'teacher_id' => 1,
            'meeting_id' => 'MEET-1234-5678-901',
            'status' => 'scheduled',
            'participant_count' => 0,
        ]);

        $response = $this->withToken($this->token)
            ->postJson('/api/v1/education/live-classes/session-1/join', [
                'user_id' => $this->user->id,
                'role' => 'student',
            ], [
                'X-Correlation-ID' => 'test-correlation-456',
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'session_id' => 'session-1',
                'role' => 'student',
            ]);
    }

    public function test_send_chat_message_success(): void
    {
        DB::table('education_live_sessions')->insert([
            'id' => 'session-1',
            'tenant_id' => tenant()->id,
            'slot_id' => 1,
            'teacher_id' => 1,
            'meeting_id' => 'MEET-1234-5678-901',
            'status' => 'active',
            'participant_count' => 1,
        ]);

        $response = $this->withToken($this->token)
            ->postJson('/api/v1/education/live-classes/session-1/chat', [
                'user_id' => $this->user->id,
                'message' => 'Hello everyone!',
                'sender_type' => 'student',
            ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'message_id',
                'sender_type',
                'message',
            ]);
    }

    public function test_trigger_ai_assistance_success(): void
    {
        DB::table('education_live_sessions')->insert([
            'id' => 'session-1',
            'tenant_id' => tenant()->id,
            'slot_id' => 1,
            'teacher_id' => 1,
            'meeting_id' => 'MEET-1234-5678-901',
            'status' => 'active',
            'participant_count' => 1,
        ]);

        $response = $this->withToken($this->token)
            ->postJson('/api/v1/education/live-classes/session-1/ai-assist', [
                'message' => 'How do I solve this problem?',
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'sender_type' => 'ai',
            ]);
    }

    public function test_start_session_success(): void
    {
        DB::table('education_live_sessions')->insert([
            'id' => 'session-1',
            'tenant_id' => tenant()->id,
            'slot_id' => 1,
            'teacher_id' => 1,
            'meeting_id' => 'MEET-1234-5678-901',
            'status' => 'scheduled',
            'participant_count' => 1,
        ]);

        $response = $this->withToken($this->token)
            ->postJson('/api/v1/education/live-classes/session-1/start');

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Session started',
            ]);
    }

    public function test_end_session_success(): void
    {
        DB::table('education_live_sessions')->insert([
            'id' => 'session-1',
            'tenant_id' => tenant()->id,
            'slot_id' => 1,
            'teacher_id' => 1,
            'meeting_id' => 'MEET-1234-5678-901',
            'status' => 'active',
            'participant_count' => 1,
        ]);

        $response = $this->withToken($this->token)
            ->postJson('/api/v1/education/live-classes/session-1/end');

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Session ended',
            ]);
    }
}
