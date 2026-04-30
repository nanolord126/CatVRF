<?php

declare(strict_types=1);

namespace Modules\Video\Application\Services;

use App\Traits\WithAuditLogging;
use App\Services\Security\AuditService;
use Illuminate\Log\LogManager;
use Illuminate\Filesystem\FilesystemManager;
use Modules\Video\Domain\ValueObjects\RoomType;

/**
 * VideoOptimizationService — Сервис для оптимизации видео
 *
 * CatVRF 2026 Canon - Production Mandatory
 * - Dependency injection instead of facades
 * - Readonly class
 * - Audit logging
 */
final readonly class VideoOptimizationService
{
    use WithAuditLogging;

    private const QUALITY_PROFILES = [
        '360p' => ['-s', '640x360', '-b:v', '800k', '-maxrate', '856k', '-bufsize', '1200k'],
        '480p' => ['-s', '854x480', '-b:v', '1400k', '-maxrate', '1498k', '-bufsize', '2100k'],
        '720p' => ['-s', '1280x720', '-b:v', '2800k', '-maxrate', '2996k', '-bufsize', '4200k'],
        '1080p' => ['-s', '1920x1080', '-b:v', '5000k', '-maxrate', '5350k', '-bufsize', '7500k'],
        '1440p' => ['-s', '2560x1440', '-b:v', '9000k', '-maxrate', '9630k', '-bufsize', '13500k'],
    ];

    public function __construct(
        private readonly AuditService $auditService,
        private readonly LogManager $log,
        private readonly FilesystemManager $storage,
    ) {}

    public function processRecording(string $inputPath, RoomType $roomType): string
    {
        $tenantId = tenant('id') ?? 'default';
        $outputDir = "tenant/{$tenantId}/video/recordings/" . date('Y/m/d');
        $outputBaseName = pathinfo($inputPath, PATHINFO_FILENAME);

        $this->logAction('video_recording_processing_started', 'VideoRecording', null, [
            'input_path' => $inputPath,
            'room_type' => $roomType->value,
            'tenant_id' => $tenantId,
        ], null, $tenantId);

        // Generate HLS stream with adaptive bitrate
        $hlsOutputPath = $this->generateHlsStream($inputPath, $outputDir, $outputBaseName, $roomType);

        // Upload to CDN if enabled
        if (config('cdn.enabled')) {
            $cdnUrl = $this->uploadToCdn($hlsOutputPath);
            $this->logAction('video_recording_uploaded_to_cdn', 'VideoRecording', null, [
                'output_path' => $hlsOutputPath,
                'cdn_url' => $cdnUrl,
            ], null, $tenantId);
            return $cdnUrl;
        }

        $this->logAction('video_recording_processed', 'VideoRecording', null, [
            'output_path' => $hlsOutputPath,
        ], null, $tenantId);

        return $this->storage->url($hlsOutputPath);
    }

    private function generateHlsStream(
        string $inputPath,
        string $outputDir,
        string $outputBaseName,
        RoomType $roomType,
    ): string {
        $qualities = $this->getQualitiesForRoomType($roomType);
        $localInputPath = $this->storage->path($inputPath);
        $localOutputDir = storage_path('app/' . $outputDir);

        if (!is_dir($localOutputDir)) {
            mkdir($localOutputDir, 0755, true);
        }

        $segmentFilename = "{$outputBaseName}_%v_%03d.ts";
        $playlistFilename = "{$outputBaseName}.m3u8";
        $masterPlaylistPath = "{$localOutputDir}/{$playlistFilename}";

        // Build FFmpeg command for HLS with adaptive bitrate
        $command = $this->buildFFmpegCommand(
            $localInputPath,
            $localOutputDir,
            $segmentFilename,
            $masterPlaylistPath,
            $qualities,
        );

        $process = proc_open($command, [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ], $pipes);

        if (!is_resource($process)) {
            throw new \RuntimeException('Failed to start FFmpeg process');
        }

        $output = stream_get_contents($pipes[1]);
        $error = stream_get_contents($pipes[2]);
        fclose($pipes[0]);
        fclose($pipes[1]);
        fclose($pipes[2]);

        $returnCode = proc_close($process);

        if ($returnCode !== 0) {
            $this->log->error('FFmpeg failed', [
                'command' => $command,
                'error' => $error,
                'output' => $output,
            ]);
            throw new \RuntimeException("FFmpeg processing failed: {$error}");
        }

        $tenantId = tenant('id') ?? 'default';
        $this->logAction('hls_stream_generated', 'VideoRecording', null, [
            'output_path' => $masterPlaylistPath,
            'qualities' => array_keys($qualities),
            'tenant_id' => $tenantId,
        ], null, $tenantId);

        return "{$outputDir}/{$playlistFilename}";
    }

    /**
     * @return array<string, array<int, string>>
     */
    private function getQualitiesForRoomType(RoomType $roomType): array
    {
        return match ($roomType) {
            RoomType::CONSULTATION => [
                '480p' => self::QUALITY_PROFILES['480p'],
                '720p' => self::QUALITY_PROFILES['720p'],
            ],
            RoomType::GROOMING_DEMO, RoomType::MASTERCLASS => [
                '480p' => self::QUALITY_PROFILES['480p'],
                '720p' => self::QUALITY_PROFILES['720p'],
                '1080p' => self::QUALITY_PROFILES['1080p'],
            ],
            RoomType::SURGERY => [
                '720p' => self::QUALITY_PROFILES['720p'],
                '1080p' => self::QUALITY_PROFILES['1080p'],
                '1440p' => self::QUALITY_PROFILES['1440p'],
            ],
            RoomType::GROUP_CALL => [
                '360p' => self::QUALITY_PROFILES['360p'],
                '480p' => self::QUALITY_PROFILES['480p'],
                '720p' => self::QUALITY_PROFILES['720p'],
            ],
        };
    }

    /**
     * @param array<string, array<int, string>> $qualities
     */
    private function buildFFmpegCommand(
        string $inputPath,
        string $outputDir,
        string $segmentFilename,
        string $masterPlaylistPath,
        array $qualities,
    ): string {
        $ffmpegPath = config('video.ffmpeg_path', 'ffmpeg');

        $command = [
            $ffmpegPath,
            '-i', $inputPath,
            '-c:v', 'libx264',
            '-c:a', 'aac',
            '-preset', 'slow',
            '-crf', '23',
            '-g', '48',
            '-sc_threshold', '0',
            '-hls_time', '4',
            '-hls_playlist_type', 'vod',
            '-hls_segment_filename', "{$outputDir}/{$segmentFilename}",
        ];

        // Add quality variants
        $mapIndex = 0;
        foreach ($qualities as $qualityName => $qualityParams) {
            $command[] = '-map';
            $command[] = '0:v:0';
            $command[] = '-map';
            $command[] = '0:a:0';
            $command = array_merge($command, $qualityParams);
            $command[] = '-f';
            $command[] = 'hls';
            $command[] = "-hls_list_size";
            $command[] = "0";
            $command[] = "{$outputDir}/{$qualityName}.m3u8";
            $mapIndex++;
        }

        // Create master playlist
        $masterPlaylistContent = "#EXTM3U\n";
        foreach (array_keys($qualities) as $qualityName) {
            $bandwidth = $this->getBandwidthForQuality($qualityName);
            $resolution = $this->getResolutionForQuality($qualityName);
            $masterPlaylistContent .= "#EXT-X-STREAM-INF:BANDWIDTH={$bandwidth},RESOLUTION={$resolution}\n";
            $masterPlaylistContent .= "{$qualityName}.m3u8\n";
        }

        file_put_contents($masterPlaylistPath, $masterPlaylistContent);

        return implode(' ', array_map('escapeshellarg', $command));
    }

    private function getBandwidthForQuality(string $quality): int
    {
        return match ($quality) {
            '360p' => 800000,
            '480p' => 1400000,
            '720p' => 2800000,
            '1080p' => 5000000,
            '1440p' => 9000000,
            default => 2800000,
        };
    }

    private function getResolutionForQuality(string $quality): string
    {
        return match ($quality) {
            '360p' => '640x360',
            '480p' => '854x480',
            '720p' => '1280x720',
            '1080p' => '1920x1080',
            '1440p' => '2560x1440',
            default => '1280x720',
        };
    }

    private function uploadToCdn(string $hlsPath): string
    {
        $cdnProvider = config('cdn.provider');

        return match ($cdnProvider) {
            'bunny' => $this->uploadToBunnyStream($hlsPath),
            'cloudflare' => $this->uploadToCloudflareStream($hlsPath),
            default => $this->storage->url($hlsPath),
        };
    }

    private function uploadToBunnyStream(string $hlsPath): string
    {
        $libraryId = config('cdn.bunny.stream_library_id');
        $apiKey = config('cdn.bunny.api_key');

        if (empty($libraryId) || empty($apiKey)) {
            return $this->storage->url($hlsPath);
        }

        $client = new \GuzzleHttp\Client();
        $localPath = $this->storage->path($hlsPath);
        $directory = dirname($hlsPath);

        // Upload all HLS segments and playlist
        $files = glob(dirname($localPath) . '/*');
        $videoId = (string) \Illuminate\Support\Str::uuid();

        foreach ($files as $file) {
            $filename = basename($file);
            $client->post("https://video.bunnycdn.com/library/{$libraryId}/videos/{$videoId}", [
                'headers' => [
                    'AccessKey' => $apiKey,
                ],
                'multipart' => [
                    [
                        'name' => 'file',
                        'contents' => fopen($file, 'r'),
                        'filename' => $filename,
                    ],
                ],
            ]);
        }

        return "https://iframe.mediadelivery.net/embed/{$libraryId}/{$videoId}";
    }

    private function uploadToCloudflareStream(string $hlsPath): string
    {
        $accountId = config('cdn.cloudflare.account_id');
        $apiKey = config('cdn.cloudflare.api_key');

        if (empty($accountId) || empty($apiKey)) {
            return $this->storage->url($hlsPath);
        }

        $client = new \GuzzleHttp\Client();
        $localPath = $this->storage->path($hlsPath);

        // Upload video to Cloudflare Stream
        $response = $client->post("https://api.cloudflare.com/client/v4/accounts/{$accountId}/stream", [
            'headers' => [
                'Authorization' => "Bearer {$apiKey}",
            ],
            'multipart' => [
                [
                    'name' => 'file',
                    'contents' => fopen($localPath, 'r'),
                ],
                [
                    'name' => 'max_duration_seconds',
                    'contents' => '3600',
                ],
            ],
        ]);

        $data = json_decode($response->getBody()->getContents(), true);
        $videoId = $data['result']['uid'] ?? null;

        if ($videoId === null) {
            throw new \RuntimeException('Failed to upload to Cloudflare Stream');
        }

        return "https://customer-{$accountId}.cloudflarestream.com/{$videoId}/manifest/video.m3u8";
    }

    public function generateThumbnail(string $videoPath, string $outputPath, int $timestamp = 5): string
    {
        $ffmpegPath = config('video.ffmpeg_path', 'ffmpeg');
        $localVideoPath = $this->storage->path($videoPath);
        $localOutputPath = $this->storage->path($outputPath);
        $outputDir = dirname($localOutputPath);

        if (!is_dir($outputDir)) {
            mkdir($outputDir, 0755, true);
        }

        $command = sprintf(
            '%s -i %s -ss %d -vframes 1 -vf "scale=320:-1" %s',
            escapeshellarg($ffmpegPath),
            escapeshellarg($localVideoPath),
            $timestamp,
            escapeshellarg($localOutputPath)
        );

        exec($command, $output, $returnCode);

        if ($returnCode !== 0) {
            $this->log->error('Failed to generate thumbnail', [
                'command' => $command,
                'error' => implode("\n", $output),
            ]);
            throw new \RuntimeException('Failed to generate thumbnail');
        }

        return $outputPath;
    }
}
