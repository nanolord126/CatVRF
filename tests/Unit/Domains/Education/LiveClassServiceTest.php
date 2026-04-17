<?php declare(strict_types=1);

namespace Tests\Unit\Domains\Education;

use Tests\TestCase;
use App\Domains\Education\Services\LiveClassService;
use App\Services\FraudControlService;
use App\Services\AuditService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Mockery;

final class LiveClassServiceTest extends TestCase
{
    private LiveClassService $service;
    private FraudControlService $fraud;
    private AuditService $audit;

    protected function setUp(): void
    {
        parent::setUp();

        $this->fraud = Mockery::mock(FraudControlService::class);
        $this->audit = Mockery::mock(AuditService::class);

        $this->service = new LiveClassService(
            $this->audit,
            $this->fraud,
        );
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_create_live_session(): void
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

        $this->audit->shouldReceive('record')->once();

        $result = $this->service->createLiveSession(1, 'test-correlation');

        $this->assertArrayHasKey('session_id', $result);
        $this->assertArrayHasKey('meeting_id', $result);
        $this->assertArrayHasKey('teacher_token', $result);
        $this->assertEquals('scheduled', $result['status']);
    }

    public function test_join_session(): void
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

        $result = $this->service->joinSession('session-1', 2, 'student', 'test-correlation');

        $this->assertArrayHasKey('session_id', $result);
        $this->assertArrayHasKey('token', $result);
        $this->assertEquals('student', $result['role']);
    }

    public function test_send_chat_message(): void
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

        Redis::shouldReceive('lpush')->once();
        Redis::shouldReceive('ltrim')->once();
        Redis::shouldReceive('expire')->once();

        $result = $this->service->sendChatMessage('session-1', 2, 'Hello', 'student', 'test-correlation');

        $this->assertArrayHasKey('message_id', $result);
        $this->assertEquals('student', $result['sender_type']);
        $this->assertEquals('Hello', $result['message']);
    }

    public function test_trigger_ai_assistance(): void
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

        $this->fraud->shouldReceive('check')->once();
        Redis::shouldReceive('lpush')->once();
        Redis::shouldReceive('ltrim')->once();
        Redis::shouldReceive('expire')->once();

        $result = $this->service->triggerAIAssistance('session-1', 'How do I solve this?', 'test-correlation');

        $this->assertArrayHasKey('message_id', $result);
        $this->assertEquals('ai', $result['sender_type']);
    }

    public function test_get_chat_history(): void
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

        DB::table('education_live_chat_messages')->insert([
            'id' => 'msg-1',
            'session_id' => 'session-1',
            'user_id' => 2,
            'sender_type' => 'student',
            'message' => 'Hello',
        ]);

        $history = $this->service->getChatHistory('session-1');

        $this->assertIsArray($history);
        $this->assertGreaterThan(0, count($history));
    }
}
