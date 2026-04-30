<?php

declare(strict_types=1);

namespace App\Services\Video;

use Illuminate\Log\LogManager;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;

final readonly class VideoOptimizationService
{
    private const THUMBNAIL_WIDTH = 320;
    private const THUMBNAIL_TIMEOUT_SECONDS = 60;
    private const HLS_TIMEOUT_SECONDS = 1800;
    private const HLS_SEGMENT_TIME_SECONDS = 10;
    private const VIDEO_VARIANTS = [
        '360p' => ['width' => 640, 'height' => 360, 'bitrate' => '800k', 'audio_bitrate' => '128k'],
        '720p' => ['width' => 1280, 'height' => 720, 'bitrate' => '2800k', 'audio_bitrate' => '128k'],
        '1080p' => ['width' => 1920, 'height' => 1080, 'bitrate' => '5000k', 'audio_bitrate' => '192k'],
    ];

    public function __construct(
        private readonly LogManager $logger,
    ) {
        $this->ffmpegPath = config('video.ffmpeg_path', '/usr/bin/ffmpeg');
    }

    private string $ffmpegPath;

    /**
     * Get video metadata using FFprobe
     *
     * @param  string  $filePath
     * @return array
     */
    public function getVideoMetadata(string $filePath): array
    {
        $ffprobePath = config('video.ffprobe_path', '/usr/bin/ffprobe');

        $command = [
            $ffprobePath,
            '-v', 'error',
            '-show_entries', 'format=duration,size',
            '-show_entries', 'stream=width,height,codec_name',
            '-of', 'json',
            $filePath,
        ];

        $process = new Process($command);
        $process->run();

        if (!$process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }

        $output = json_decode($process->getOutput(), true);

        return [
            'duration' => (int) ($output['format']['duration'] ?? 0),
            'width' => $output['streams'][0]['width'] ?? null,
            'height' => $output['streams'][0]['height'] ?? null,
            'codec' => $output['streams'][0]['codec_name'] ?? null,
            'size' => $output['format']['size'] ?? 0,
        ];
    }

    /**
     * Generate thumbnail from video
     *
     * @param  string  $filePath
     * @param  string  $recordingId
     * @param  int  $timestamp  Timestamp in seconds
     * @return string|null  Path to generated thumbnail
     */
    public function generateThumbnail(string $filePath, string $recordingId, int $timestamp = 5): ?string
    {
        $outputPath = tempnam(sys_get_temp_dir(), 'thumb_') . '.jpg';

        $command = [
            $this->ffmpegPath,
            '-i', $filePath,
            '-ss', (string) $timestamp,
            '-vframes', '1',
            '-vf', 'scale=' . self::THUMBNAIL_WIDTH . ':-1',
            '-y',
            $outputPath,
        ];

        $process = new Process($command);
        $process->setTimeout(self::THUMBNAIL_TIMEOUT_SECONDS);
        $process->run();

        if (!$process->isSuccessful()) {
            $this->logger->error('Failed to generate thumbnail', [
                'error' => $process->getErrorOutput(),
            ]);
            return null;
        }

        return file_exists($outputPath) ? $outputPath : null;
    }

    /**
     * Convert video to HLS with multiple quality variants
     *
     * @param  string  $filePath
     * @param  int  $tenantId
     * @param  string  $roomId
     * @param  string  $recordingId
     * @return array  Array of quality variants with playlists and segments
     */
    public function convertToHlsWithVariants(
        string $filePath,
        int $tenantId,
        string $roomId,
        string $recordingId
    ): array {
        $variants = self::VIDEO_VARIANTS;

        $results = [];

        foreach ($variants as $quality => $config) {
            $outputDir = sys_get_temp_dir() . "/hls_{$quality}_{$recordingId}/";
            if (!is_dir($outputDir)) {
                mkdir($outputDir, 0755, true);
            }

            $outputPlaylist = $outputDir . 'playlist.m3u8';

            $command = [
                $this->ffmpegPath,
                '-i', $filePath,
                '-c:v', 'libx264',
                '-preset', 'slow',
                '-crf', '23',
                '-maxrate', $config['bitrate'],
                '-bufsize', (int) ($config['bitrate'] * 2),
                '-vf', "scale={$config['width']}:{$config['height']}",
                '-c:a', 'aac',
                '-b:a', $config['audio_bitrate'],
                '-hls_time', (string) self::HLS_SEGMENT_TIME_SECONDS,
                '-hls_playlist_type', 'vod',
                '-hls_segment_filename', $outputDir . 'segment_%03d.ts',
                '-y',
                $outputPlaylist,
            ];

            $process = new Process($command);
            $process->setTimeout(self::HLS_TIMEOUT_SECONDS); // 30 minutes
            $process->run();

            if (!$process->isSuccessful()) {
                $this->logger->error("Failed to convert video to HLS for quality {$quality}", [
                    'error' => $process->getErrorOutput(),
                ]);
                continue;
            }

            // Read playlist and segments
            $playlistContent = file_get_contents($outputPlaylist);
            $segments = [];

            foreach (glob($outputDir . 'segment_*.ts') as $segmentFile) {
                $segments[basename($segmentFile)] = file_get_contents($segmentFile);
            }

            $results[$quality] = [
                'playlist' => $playlistContent,
                'segments' => $segments,
                'bandwidth' => $this->calculateBandwidth($config['bitrate'], $config['audio_bitrate']),
                'resolution' => "{$config['width']}x{$config['height']}",
            ];

            // Clean up
            array_map('unlink', glob($outputDir . '*'));
            rmdir($outputDir);
        }

        return $results;
    }

    /**
     * Generate master HLS playlist
     *
     * @param  array  $variants
     * @return string
     */
    public function generateMasterPlaylist(array $variants): string
    {
        $lines = ['#EXTM3U', '#EXT-X-VERSION:3'];

        foreach ($variants as $variant) {
            $lines[] = "#EXT-X-STREAM-INF:BANDWIDTH={$variant['bandwidth']},RESOLUTION={$variant['resolution']}";
            $lines[] = $variant['url'];
        }

        return implode("\n", $lines);
    }

    /**
     * Calculate bandwidth in bits per second
     *
     * @param  string  $videoBitrate  e.g., '2800k'
     * @param  string  $audioBitrate  e.g., '128k'
     * @return int
     */
    private function calculateBandwidth(string $videoBitrate, string $audioBitrate): int
    {
        $video = (int) rtrim($videoBitrate, 'k') * 1000;
        $audio = (int) rtrim($audioBitrate, 'k') * 1000;
        
        return (int) (($video + $audio) * 1.1); // 10% overhead
    }

    /**
     * Get optimized playback URL for recording
     *
     * @param  \App\Models\Video\VideoRecording  $recording
     * @param  string  $quality
     * @return string
     */
    public function getOptimizedPlaybackUrl($recording, string $quality = 'auto'): string
    {
        if (config('cdn.video_provider') === 'bunny_stream' && $recording->isCdnSynced()) {
            return $this->getBunnyStreamUrl($recording, $quality);
        }

        if (config('cdn.video_provider') === 'cloudflare_stream' && $recording->isCdnSynced()) {
            return $this->getCloudflareStreamUrl($recording, $quality);
        }

        // Fallback to local HLS
        if ($recording->hls_playlist_url) {
            return $recording->hls_playlist_url;
        }

        return $recording->getFullStoragePath();
    }

    /**
     * Get Bunny Stream URL with adaptive bitrate
     *
     * @param  \App\Models\Video\VideoRecording  $recording
     * @param  string  $quality
     * @return string
     */
    private function getBunnyStreamUrl($recording, string $quality): string
    {
        $baseUrl = $recording->cdn_url;
        
        if ($quality === 'auto') {
            return $baseUrl; // Bunny Stream handles ABR automatically
        }

        return str_replace('playlist.m3u8', "{$quality}/playlist.m3u8", $baseUrl);
    }

    /**
     * Get Cloudflare Stream signed URL
     *
     * @param  \App\Models\Video\VideoRecording  $recording
     * @param  string  $quality
     * @return string
     */
    private function getCloudflareStreamUrl($recording, string $quality): string
    {
        $baseUrl = $recording->cdn_url;
        
        // Cloudflare Stream handles ABR automatically
        return $baseUrl;
    }

    /**
     * Apply watermark to video
     *
     * @param  string  $inputPath
     * @param  string  $outputPath
     * @param  string  $watermarkText
     * @return bool
     */
    public function applyWatermark(string $inputPath, string $outputPath, string $watermarkText): bool
    {
        $command = [
            $this->ffmpegPath,
            '-i', $inputPath,
            '-vf', "drawtext=text='{$watermarkText}':fontfile=/path/to/font.ttf:fontsize=24:fontcolor=white@0.5:x=10:y=H-th-10",
            '-c:a', 'copy',
            '-y',
            $outputPath,
        ];

        $process = new Process($command);
        $process->setTimeout(1800);
        $process->run();

        return $process->isSuccessful();
    }
}
