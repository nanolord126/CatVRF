<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\Beauty\Services;

use App\Domains\Beauty\DTOs\VideoCallDto;
use App\Domains\Beauty\Services\VideoCallService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class VideoCallServiceTest extends TestCase
{
    use RefreshDatabase;

    private VideoCallService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(VideoCallService::class);
    }

    public function test_initiate_video_call(): void
    {
        $dto = new VideoCallDto(
            tenantId: 1,
            businessGroupId: null,
            userId: 1,
            masterId: 1,
            scheduledFor: null,
            durationMinutes: 5,
            correlationId: 'test-correlation',
        );

        $result = $this->service->initiate($dto);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('success', $result);
        $this->assertArrayHasKey('call_id', $result);
        $this->assertArrayHasKey('token', $result);
        $this->assertArrayHasKey('room_name', $result);
        $this->assertTrue($result['success']);
    }

    public function test_end_video_call(): void
    {
        $dto = new VideoCallDto(
            tenantId: 1,
            businessGroupId: null,
            userId: 1,
            masterId: 1,
            scheduledFor: null,
            durationMinutes: 5,
            correlationId: 'test-correlation',
        );

        $initResult = $this->service->initiate($dto);
        $callId = $initResult['call_id'];

        $endResult = $this->service->end($callId, 300, 'user_ended');

        $this->assertIsArray($endResult);
        $this->assertArrayHasKey('success', $endResult);
        $this->assertTrue($endResult['success']);
    }

    public function test_duration_capped_at_max(): void
    {
        $dto = new VideoCallDto(
            tenantId: 1,
            businessGroupId: null,
            userId: 1,
            masterId: 1,
            scheduledFor: null,
            durationMinutes: 35,
            correlationId: 'test-correlation',
        );

        $result = $this->service->initiate($dto);

        $this->assertLessThanOrEqual(1800, $result['duration_seconds']);
    }
}
