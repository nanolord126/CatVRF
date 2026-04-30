<?php

declare(strict_types=1);

namespace Tests\Fraud;

use Modules\Video\Domain\Entities\Room;
use Modules\Video\Domain\Entities\Participant;
use Modules\Video\Domain\Enums\RoomStatus;
use Illuminate\Support\Facades\Cache;

final class VideoFraudDetectionTest extends BaseFraudTest
{
    public function test_fake_room_creation(): void
    {
        $userId = 1;

        for ($i = 0; $i < 20; $i++) {
            Room::create([
                'host_id' => $userId,
                'tenant_id' => 1,
                'title' => "Room {$i}",
                'status' => RoomStatus::Created,
                'max_participants' => 100,
                'duration' => 60,
            ]);
        }

        $this->fraudControl->checkResourceHoarding([
            'user_id' => $userId,
            'resource_type' => 'video_room',
            'resource_count' => 20,
            'time_window_minutes' => 30,
        ]);

        $this->assertFraudAlertCreated(
            'user',
            $userId,
            FraudType::ResourceHoarding,
            FraudSeverity::Medium
        );
    }

    public function test_fake_participant_join(): void
    {
        $roomId = 1;
        $deviceFingerprint = 'fp_video_bot';

        for ($i = 0; $i < 100; $i++) {
            Participant::create([
                'room_id' => $roomId,
                'user_id' => $i + 1000,
                'device_fingerprint' => $deviceFingerprint,
                'joined_at' => now(),
                'duration_seconds' => 0, // Immediately left
            ]);
        }

        $this->fraudControl->checkParticipantFraud([
            'room_id' => $roomId,
            'device_fingerprint' => $deviceFingerprint,
            'participant_count' => 100,
            'avg_duration' => 0,
        ]);

        $this->assertFraudAlertCreated(
            'room',
            $roomId,
            FraudType::Spam,
            FraudSeverity::High
        );
    }

    public function test_bandwidth_abuse(): void
    {
        $userId = 1;

        for ($i = 0; $i < 10; $i++) {
            Room::create([
                'host_id' => $userId,
                'tenant_id' => 1,
                'title' => "High bandwidth room {$i}",
                'status' => RoomStatus::Active,
                'bandwidth_used_gb' => 100, // Unusually high
                'duration' => 3600, // 1 hour
            ]);
        }

        $this->fraudControl->checkBandwidthAbuse([
            'user_id' => $userId,
            'total_bandwidth_gb' => 1000,
            'room_count' => 10,
            'time_window_hours' => 1,
        ]);

        $this->assertFraudAlertCreated(
            'user',
            $userId,
            FraudType::ResourceAbuse,
            FraudSeverity::High
        );
    }

    public function test_recording_without_consent(): void
    {
        $roomId = 1;
        $hostId = 1;

        $this->fraudControl->checkRecordingConsent([
            'room_id' => $roomId,
            'host_id' => $hostId,
            'recording_enabled' => true,
            'participants_consent' => false,
            'participant_count' => 50,
        ]);

        $this->assertFraudAlertCreated(
            'room',
            $roomId,
            FraudType::PrivacyViolation,
            FraudSeverity::Critical
        );
    }

    public function test_fake_rating_manipulation(): void
    {
        $hostId = 1;
        $deviceFingerprint = 'fp_video_bot';

        for ($i = 0; $i < 40; $i++) {
            $this->fraudControl->checkReviewManipulation([
                'entity_type' => 'video_host',
                'entity_id' => $hostId,
                'user_id' => $i + 1000,
                'device_fingerprint' => $deviceFingerprint,
                'rating' => 5,
                'comment' => 'Great host!',
            ]);
        }

        $this->assertFraudAlertCreated(
            'user',
            $hostId,
            FraudType::FakeReviews,
            FraudSeverity::High
        );
    }
}
