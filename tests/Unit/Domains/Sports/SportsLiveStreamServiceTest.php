<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\Sports;

use App\Domains\Sports\DTOs\LiveStreamSessionDto;
use App\Domains\Sports\Services\SportsLiveStreamService;
use App\Services\FraudControlService;
use App\Services\AuditService;
use Illuminate\Database\DatabaseManager;
use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Redis\Connections\Connection as RedisConnection;
use Tests\TestCase;

final class SportsLiveStreamServiceTest extends TestCase
{
    use RefreshDatabase;

    private SportsLiveStreamService $service;
    private FraudControlService $fraud;
    private AuditService $audit;
    private DatabaseManager $db;
    private Cache $cache;
    private RedisConnection $redis;

    protected function setUp(): void
    {
        parent::setUp();

        $this->fraud = $this->createMock(FraudControlService::class);
        $this->fraud->method('check')->willReturn(null);

        $this->audit = $this->createMock(AuditService::class);
        $this->db = $this->app->make(DatabaseManager::class);
        $this->cache = $this->app->make(Cache::class);
        $this->redis = $this->app->make('redis')->connection();

        $this->service = new SportsLiveStreamService(
            fraud: $this->fraud,
            audit: $this->audit,
            db: $this->db,
            cache: $this->cache,
            logger: $this->app->make('log'),
            redis: $this->redis,
        );
    }

    public function test_create_live_stream_success(): void
    {
        $this->fraud->expects($this->once())
            ->method('check')
            ->with(
                $this->equalTo(1),
                $this->equalTo('live_stream_creation'),
                $this->equalTo(0),
                $this->equalTo('test-correlation-id')
            );

        $dto = new LiveStreamSessionDto(
            userId: 1,
            tenantId: 1,
            businessGroupId: null,
            trainerId: 1,
            sessionTitle: 'Morning Yoga',
            sessionDescription: 'Relaxing morning yoga session',
            scheduledStart: now()->addHours(2)->toDateTimeString(),
            scheduledEnd: now()->addHours(3)->toDateTimeString(),
            streamType: 'yoga',
            maxParticipants: 50,
            tags: ['morning', 'yoga', 'relaxing'],
            correlationId: 'test-correlation-id',
        );

        $result = $this->service->createLiveStream($dto);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('stream_id', $result);
        $this->assertArrayHasKey('room_name', $result);
        $this->assertArrayHasKey('status', $result);
        $this->assertEquals('scheduled', $result['status']);
        $this->assertNotEmpty($result['room_name']);
    }

    public function test_start_live_stream_success(): void
    {
        $this->fraud->expects($this->once())
            ->method('check')
            ->with(
                $this->equalTo(1),
                $this->equalTo('live_stream_start'),
                $this->equalTo(0),
                $this->equalTo('test-correlation-id')
            );

        $dto = new LiveStreamSessionDto(
            userId: 1,
            tenantId: 1,
            businessGroupId: null,
            trainerId: 1,
            sessionTitle: 'Test Stream',
            sessionDescription: 'Test description',
            scheduledStart: now()->addHours(2)->toDateTimeString(),
            scheduledEnd: now()->addHours(3)->toDateTimeString(),
            streamType: 'fitness',
            maxParticipants: 50,
            tags: [],
            correlationId: 'test-correlation-id',
        );

        $createResult = $this->service->createLiveStream($dto);
        $streamId = $createResult['stream_id'];

        $startResult = $this->service->startLiveStream($streamId, 1, 'test-correlation-id');

        $this->assertIsArray($startResult);
        $this->assertArrayHasKey('status', $startResult);
        $this->assertEquals('live', $startResult['status']);
        $this->assertArrayHasKey('started_at', $startResult);
    }

    public function test_join_live_stream_success(): void
    {
        $this->fraud->expects($this->exactly(2))
            ->method('check');

        $dto = new LiveStreamSessionDto(
            userId: 1,
            tenantId: 1,
            businessGroupId: null,
            trainerId: 1,
            sessionTitle: 'Test Stream',
            sessionDescription: 'Test description',
            scheduledStart: now()->addHours(2)->toDateTimeString(),
            scheduledEnd: now()->addHours(3)->toDateTimeString(),
            streamType: 'fitness',
            maxParticipants: 50,
            tags: [],
            correlationId: 'test-correlation-id',
        );

        $createResult = $this->service->createLiveStream($dto);
        $streamId = $createResult['stream_id'];

        $this->service->startLiveStream($streamId, 1, 'test-correlation-id');

        $joinResult = $this->service->joinLiveStream($streamId, 2, 'test-correlation-id');

        $this->assertIsArray($joinResult);
        $this->assertArrayHasKey('success', $joinResult);
        $this->assertTrue($joinResult['success']);
        $this->assertArrayHasKey('participant_token', $joinResult);
    }

    public function test_leave_live_stream_success(): void
    {
        $this->fraud->expects($this->exactly(3))
            ->method('check');

        $dto = new LiveStreamSessionDto(
            userId: 1,
            tenantId: 1,
            businessGroupId: null,
            trainerId: 1,
            sessionTitle: 'Test Stream',
            sessionDescription: 'Test description',
            scheduledStart: now()->addHours(2)->toDateTimeString(),
            scheduledEnd: now()->addHours(3)->toDateTimeString(),
            streamType: 'fitness',
            maxParticipants: 50,
            tags: [],
            correlationId: 'test-correlation-id',
        );

        $createResult = $this->service->createLiveStream($dto);
        $streamId = $createResult['stream_id'];

        $this->service->startLiveStream($streamId, 1, 'test-correlation-id');
        $this->service->joinLiveStream($streamId, 2, 'test-correlation-id');

        $leaveResult = $this->service->leaveLiveStream($streamId, 2, 'test-correlation-id');

        $this->assertIsArray($leaveResult);
        $this->assertArrayHasKey('success', $leaveResult);
        $this->assertTrue($leaveResult['success']);
    }

    public function test_end_live_stream_success(): void
    {
        $this->fraud->expects($this->exactly(2))
            ->method('check');

        $dto = new LiveStreamSessionDto(
            userId: 1,
            tenantId: 1,
            businessGroupId: null,
            trainerId: 1,
            sessionTitle: 'Test Stream',
            sessionDescription: 'Test description',
            scheduledStart: now()->addHours(2)->toDateTimeString(),
            scheduledEnd: now()->addHours(3)->toDateTimeString(),
            streamType: 'fitness',
            maxParticipants: 50,
            tags: [],
            correlationId: 'test-correlation-id',
        );

        $createResult = $this->service->createLiveStream($dto);
        $streamId = $createResult['stream_id'];

        $this->service->startLiveStream($streamId, 1, 'test-correlation-id');

        $endResult = $this->service->endLiveStream($streamId, 1, 'test-correlation-id');

        $this->assertIsArray($endResult);
        $this->assertArrayHasKey('success', $endResult);
        $this->assertTrue($endResult['success']);
        $this->assertArrayHasKey('ended_at', $endResult);
    }

    public function test_get_active_streams_success(): void
    {
        $this->fraud->expects($this->exactly(2))
            ->method('check');

        $dto = new LiveStreamSessionDto(
            userId: 1,
            tenantId: 1,
            businessGroupId: null,
            trainerId: 1,
            sessionTitle: 'Test Stream',
            sessionDescription: 'Test description',
            scheduledStart: now()->addHours(2)->toDateTimeString(),
            scheduledEnd: now()->addHours(3)->toDateTimeString(),
            streamType: 'fitness',
            maxParticipants: 50,
            tags: [],
            correlationId: 'test-correlation-id',
        );

        $this->service->createLiveStream($dto);
        $streamId = $this->db->table('sports_live_streams')->where('trainer_id', 1)->value('id');
        $this->db->table('sports_live_streams')->where('id', $streamId)->update(['status' => 'live']);

        $result = $this->service->getActiveStreams(null, 0, 'test-correlation-id');

        $this->assertIsArray($result);
        $this->assertNotEmpty($result);
    }

    public function test_get_stream_recording_success(): void
    {
        $this->fraud->expects($this->exactly(3))
            ->method('check');

        $dto = new LiveStreamSessionDto(
            userId: 1,
            tenantId: 1,
            businessGroupId: null,
            trainerId: 1,
            sessionTitle: 'Test Stream',
            sessionDescription: 'Test description',
            scheduledStart: now()->addHours(2)->toDateTimeString(),
            scheduledEnd: now()->addHours(3)->toDateTimeString(),
            streamType: 'fitness',
            maxParticipants: 50,
            tags: [],
            correlationId: 'test-correlation-id',
        );

        $createResult = $this->service->createLiveStream($dto);
        $streamId = $createResult['stream_id'];

        $this->service->startLiveStream($streamId, 1, 'test-correlation-id');
        $this->service->endLiveStream($streamId, 1, 'test-correlation-id');

        $result = $this->service->getStreamRecording($streamId, 1, 'test-correlation-id');

        $this->assertIsArray($result);
        $this->assertArrayHasKey('has_recording', $result);
    }

    public function test_max_participants_limit(): void
    {
        $this->fraud->expects($this->exactly(102))
            ->method('check');

        $dto = new LiveStreamSessionDto(
            userId: 1,
            tenantId: 1,
            businessGroupId: null,
            trainerId: 1,
            sessionTitle: 'Test Stream',
            sessionDescription: 'Test description',
            scheduledStart: now()->addHours(2)->toDateTimeString(),
            scheduledEnd: now()->addHours(3)->toDateTimeString(),
            streamType: 'fitness',
            maxParticipants: 100,
            tags: [],
            correlationId: 'test-correlation-id',
        );

        $createResult = $this->service->createLiveStream($dto);
        $streamId = $createResult['stream_id'];

        $this->service->startLiveStream($streamId, 1, 'test-correlation-id');

        for ($i = 2; $i <= 100; $i++) {
            $this->service->joinLiveStream($streamId, $i, 'test-correlation-id');
        }

        $result = $this->service->joinLiveStream($streamId, 101, 'test-correlation-id');

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('maximum participants', $result['message']);
    }

    public function test_generate_webrtc_room_name(): void
    {
        $roomName = $this->callPrivateMethod($this->service, 'generateWebRTCRoomName', [123]);

        $this->assertIsString($roomName);
        $this->assertStringContainsString('sports_stream_123', $roomName);
        $this->assertGreaterThan(20, strlen($roomName));
    }

    private function callPrivateMethod(object $object, string $methodName, array $parameters): mixed
    {
        $reflection = new \ReflectionClass($object);
        $method = $reflection->getMethod($methodName);
        $method->setAccessible(true);

        return $method->invokeArgs($object, $parameters);
    }
}
