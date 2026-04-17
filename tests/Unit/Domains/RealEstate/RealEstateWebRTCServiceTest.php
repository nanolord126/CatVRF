<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\RealEstate;

use Tests\TestCase;
use App\Domains\RealEstate\Services\RealEstateWebRTCService;
use App\Domains\RealEstate\Models\Property;
use App\Domains\RealEstate\Models\PropertyViewing;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Redis;

final class RealEstateWebRTCServiceTest extends TestCase
{
    use RefreshDatabase;

    private RealEstateWebRTCService $service;
    private Tenant $tenant;
    private Property $property;
    private User $user;
    private User $agent;
    private PropertyViewing $viewing;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(RealEstateWebRTCService::class);
        $this->tenant = Tenant::factory()->create();
        $this->user = User::factory()->create();
        $this->agent = User::factory()->create();
        $this->property = Property::factory()->create([
            'tenant_id' => $this->tenant->id,
            'type' => 'apartment',
            'area_sqm' => 75.5,
            'price' => 10000000.00,
        ]);
        $this->viewing = PropertyViewing::factory()->create([
            'property_id' => $this->property->id,
            'user_id' => $this->user->id,
            'agent_id' => $this->agent->id,
        ]);
    }

    public function test_create_video_call_room_returns_valid_room(): void
    {
        $correlationId = \Illuminate\Support\Str::uuid()->toString();
        $result = $this->service->createVideoCallRoom(
            $this->property->id,
            $this->user->id,
            $this->agent->id,
            $correlationId
        );

        $this->assertIsArray($result);
        $this->assertArrayHasKey('room_id', $result);
        $this->assertArrayHasKey('status', $result);
        $this->assertArrayHasKey('expires_at', $result);
        $this->assertArrayHasKey('max_participants', $result);
        $this->assertArrayHasKey('duration_limit_seconds', $result);
        $this->assertEquals('created', $result['status']);
        $this->assertEquals(5, $result['max_participants']);
        $this->assertEquals(3600, $result['duration_limit_seconds']);
    }

    public function test_create_video_call_room_returns_existing_room_if_active(): void
    {
        $correlationId = \Illuminate\Support\Str::uuid()->toString();
        $firstResult = $this->service->createVideoCallRoom(
            $this->property->id,
            $this->user->id,
            $this->agent->id,
            $correlationId
        );

        $secondResult = $this->service->createVideoCallRoom(
            $this->property->id,
            $this->user->id,
            $this->agent->id,
            $correlationId
        );

        $this->assertEquals($firstResult['room_id'], $secondResult['room_id']);
        $this->assertEquals('existing', $secondResult['status']);
    }

    public function test_join_video_call_adds_participant(): void
    {
        $correlationId = \Illuminate\Support\Str::uuid()->toString();
        $roomResult = $this->service->createVideoCallRoom(
            $this->property->id,
            $this->user->id,
            $this->agent->id,
            $correlationId
        );

        $joinResult = $this->service->joinVideoCall(
            $roomResult['room_id'],
            $this->user->id,
            'buyer',
            $correlationId
        );

        $this->assertIsArray($joinResult);
        $this->assertEquals($roomResult['room_id'], $joinResult['room_id']);
        $this->assertEquals($this->user->id, $joinResult['participant_id']);
        $this->assertEquals('joined', $joinResult['status']);
    }

    public function test_join_video_call_prevents_duplicate_participant(): void
    {
        $correlationId = \Illuminate\Support\Str::uuid()->toString();
        $roomResult = $this->service->createVideoCallRoom(
            $this->property->id,
            $this->user->id,
            $this->agent->id,
            $correlationId
        );

        $this->service->joinVideoCall(
            $roomResult['room_id'],
            $this->user->id,
            'buyer',
            $correlationId
        );

        $secondJoin = $this->service->joinVideoCall(
            $roomResult['room_id'],
            $this->user->id,
            'buyer',
            $correlationId
        );

        $this->assertEquals('already_joined', $secondJoin['status']);
    }

    public function test_join_video_call_rejects_invalid_room(): void
    {
        $correlationId = \Illuminate\Support\Str::uuid()->toString();

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Video call room not found or expired');

        $this->service->joinVideoCall(
            'invalid_room_id',
            $this->user->id,
            'buyer',
            $correlationId
        );
    }

    public function test_leave_video_call_removes_participant(): void
    {
        $correlationId = \Illuminate\Support\Str::uuid()->toString();
        $roomResult = $this->service->createVideoCallRoom(
            $this->property->id,
            $this->user->id,
            $this->agent->id,
            $correlationId
        );

        $this->service->joinVideoCall(
            $roomResult['room_id'],
            $this->user->id,
            'buyer',
            $correlationId
        );

        $this->service->leaveVideoCall(
            $roomResult['room_id'],
            $this->user->id,
            $correlationId
        );

        $status = $this->service->getCallStatus($roomResult['room_id'], $correlationId);
        $this->assertEquals(0, $status['participant_count']);
    }

    public function test_end_video_call_terminates_room(): void
    {
        $correlationId = \Illuminate\Support\Str::uuid()->toString();
        $roomResult = $this->service->createVideoCallRoom(
            $this->property->id,
            $this->user->id,
            $this->agent->id,
            $correlationId
        );

        $this->service->joinVideoCall(
            $roomResult['room_id'],
            $this->user->id,
            'buyer',
            $correlationId
        );

        $endResult = $this->service->endVideoCall(
            $roomResult['room_id'],
            $this->user->id,
            $correlationId
        );

        $this->assertIsArray($endResult);
        $this->assertEquals('ended', $endResult['status']);
        $this->assertEquals($this->user->id, $endResult['ended_by']);
    }

    public function test_end_video_call_rejects_non_participant(): void
    {
        $correlationId = \Illuminate\Support\Str::uuid()->toString();
        $roomResult = $this->service->createVideoCallRoom(
            $this->property->id,
            $this->user->id,
            $this->agent->id,
            $correlationId
        );

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Only call participants can end the call');

        $this->service->endVideoCall(
            $roomResult['room_id'],
            99999,
            $correlationId
        );
    }

    public function test_get_call_status_returns_room_info(): void
    {
        $correlationId = \Illuminate\Support\Str::uuid()->toString();
        $roomResult = $this->service->createVideoCallRoom(
            $this->property->id,
            $this->user->id,
            $this->agent->id,
            $correlationId
        );

        $status = $this->service->getCallStatus($roomResult['room_id'], $correlationId);

        $this->assertIsArray($status);
        $this->assertEquals($roomResult['room_id'], $status['room_id']);
        $this->assertEquals('active', $status['status']);
    }

    public function test_get_call_status_returns_not_found_for_invalid_room(): void
    {
        $correlationId = \Illuminate\Support\Str::uuid()->toString();
        $status = $this->service->getCallStatus('invalid_room', $correlationId);

        $this->assertEquals('not_found', $status['status']);
    }

    public function test_generate_viewing_webrtc_creates_room_for_viewing(): void
    {
        $correlationId = \Illuminate\Support\Str::uuid()->toString();
        $result = $this->service->generateViewingWebRTC($this->viewing, $correlationId);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('room_id', $result);
        $this->assertArrayHasKey('viewing_id', $result);
        $this->assertEquals($this->viewing->id, $result['viewing_id']);
        $this->assertTrue($this->viewing->refresh()->webrtc_enabled);
    }

    protected function tearDown(): void
    {
        Redis::flushdb();
        parent::tearDown();
    }
}
