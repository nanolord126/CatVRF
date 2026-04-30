<?php

declare(strict_types=1);

namespace Tests\Fraud;

use Modules\Media\Domain\Entities\Media;
use Modules\Media\Domain\Entities\Upload;
use Modules\Media\Domain\Enums\MediaType;
use Illuminate\Support\Facades\Cache;

final class MediaFraudDetectionTest extends BaseFraudTest
{
    public function test_fake_media_uploads(): void
    {
        $userId = 1;

        for ($i = 0; $i < 50; $i++) {
            Upload::create([
                'user_id' => $userId,
                'tenant_id' => 1,
                'file_name' => "fake_media_{$i}.jpg",
                'file_size' => 0, // Empty files
                'media_type' => MediaType::Image,
                'status' => 'completed',
            ]);
        }

        $this->fraudControl->checkUploadFraud([
            'user_id' => $userId,
            'upload_count' => 50,
            'avg_file_size' => 0,
            'time_window_minutes' => 10,
        ]);

        $this->assertFraudAlertCreated(
            'user',
            $userId,
            FraudType::Spam,
            FraudSeverity::Medium
        );
    }

    public function test_copyright_infringement(): void
    {
        $userId = 1;

        for ($i = 0; $i < 20; $i++) {
            Media::create([
                'user_id' => $userId,
                'tenant_id' => 1,
                'title' => "Copied content {$i}",
                'source_url' => 'https://copyrighted-source.com',
                'license_verified' => false,
            ]);
        }

        $this->fraudControl->checkCopyrightInfringement([
            'user_id' => $userId,
            'infringement_count' => 20,
            'total_views' => 100000,
        ]);

        $this->assertFraudAlertCreated(
            'user',
            $userId,
            FraudType::CopyrightViolation,
            FraudSeverity::Critical
        );
    }

    public function test_view_count_manipulation(): void
    {
        $mediaId = 1;
        $deviceFingerprint = 'fp_view_bot';

        for ($i = 0; $i < 1000; $i++) {
            $this->fraudControl->checkViewManipulation([
                'media_id' => $mediaId,
                'device_fingerprint' => $deviceFingerprint,
                'user_id' => null, // Anonymous
            ]);
        }

        $this->assertFraudAlertCreated(
            'media',
            $mediaId,
            FraudType::ViewManipulation,
            FraudSeverity::High
        );
    }

    public function test_fake_downloads(): void
    {
        $mediaId = 1;
        $ipAddress = '192.168.1.90';

        for ($i = 0; $i < 200; $i++) {
            $this->fraudControl->checkDownloadFraud([
                'media_id' => $mediaId,
                'ip_address' => $ipAddress,
                'user_agent' => 'bot/1.0',
            ]);
        }

        $this->assertFraudAlertCreated(
            'media',
            $mediaId,
            FraudType::DownloadFraud,
            FraudSeverity::High
        );
    }

    public function test_content_farming(): void
    {
        $userId = 1;

        for ($i = 0; $i < 100; $i++) {
            Media::create([
                'user_id' => $userId,
                'tenant_id' => 1,
                'title' => "AI generated content {$i}",
                'description' => 'Short auto-generated description',
                'content_type' => 'video',
                'duration' => 10, // Very short videos
            ]);
        }

        $this->fraudControl->checkContentFarming([
            'user_id' => $userId,
            'content_count' => 100,
            'avg_duration' => 10,
            'avg_quality_score' => 30,
        ]);

        $this->assertFraudAlertCreated(
            'user',
            $userId,
            FraudType::Spam,
            FraudSeverity::Medium
        );
    }
}
