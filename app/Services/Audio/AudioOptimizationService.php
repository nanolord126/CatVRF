<?php

declare(strict_types=1);

namespace App\Services\Audio;

use Exception;
use Illuminate\Http\UploadedFile;
use Illuminate\Log\LogManager;
use Illuminate\Support\Str;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

final readonly class AudioOptimizationService
{
    public function __construct(
        private readonly LogManager $logger,
    ) {}
    private const OPUS_BITRATE = '64k';
    private const AAC_BITRATE = '128k';
    private const SAMPLE_RATE = '48000';
    private const LOUDNORM_TARGET = 'I=-16:TP=-1.5:LRA=11';

    public function processRecording(Media $recording): void
    {
        dispatch(new \App\Jobs\ProcessAudioJob($recording))->onQueue('audio-processing');
    }

    public function optimizeAndUpload(
        UploadedFile $audioFile,
        object $model,
        string $collection = 'audio',
        string $format = 'opus'
    ): Media {
        $optimizedPath = $this->runFfmpegOptimization($audioFile, $format);

        $media = $model->addMedia($optimizedPath)
            ->usingFileName(Str::uuid() . '.' . $format)
            ->toMediaCollection($collection);

        // Push to CDN
        $this->pushToCdn($media);

        // Clean up temp file
        if (file_exists($optimizedPath)) {
            unlink($optimizedPath);
        }

        return $media;
    }

    public function getSecureCdnUrl(Media $media, int $expiresMinutes = 1440): string
    {
        $provider = config('audio-cdn.provider', 'bunny');

        return match ($provider) {
            'bunny' => $this->getBunnySignedUrl($media, $expiresMinutes),
            'cloudflare' => $this->getCloudflareSignedUrl($media, $expiresMinutes),
            'aws' => $this->getAwsSignedUrl($media, $expiresMinutes),
            default => $media->getTemporaryUrl(now()->addMinutes($expiresMinutes)),
        };
    }

    private function runFfmpegOptimization(UploadedFile $file, string $format): string
    {
        $outputPath = storage_path('app/temp/optimized_' . Str::uuid() . '.' . $format);

        $codec = match ($format) {
            'opus' => 'libopus',
            'aac' => 'aac',
            'mp3' => 'libmp3lame',
            default => throw new Exception("Unsupported audio format: {$format}"),
        };

        $bitrate = match ($format) {
            'opus' => self::OPUS_BITRATE,
            'aac' => self::AAC_BITRATE,
            'mp3' => self::AAC_BITRATE,
            default => self::OPUS_BITRATE,
        };

        $command = [
            'ffmpeg',
            '-i', $file->getPathname(),
            '-vn', // Remove video if present
            '-c:a', $codec,
            '-b:a', $bitrate,
            '-ar', self::SAMPLE_RATE,
            '-af', 'loudnorm=' . self::LOUDNORM_TARGET, // Normalize audio
            '-y', // Overwrite output
            $outputPath,
        ];

        $commandString = implode(' ', array_map('escapeshellarg', $command));

        exec($commandString . ' 2>&1', $output, $returnCode);

        if ($returnCode !== 0) {
            $this->logger->error('FFmpeg optimization failed', [
                'command' => $commandString,
                'output' => implode("\n", $output),
                'return_code' => $returnCode,
            ]);
            throw new Exception('FFmpeg optimization failed: ' . implode("\n", $output));
        }

        if (!file_exists($outputPath)) {
            throw new Exception('Optimized audio file was not created');
        }

        return $outputPath;
    }

    public function extractAudioFromVideo(string $videoPath, string $outputFormat = 'opus'): string
    {
        $outputPath = storage_path('app/temp/audio_extracted_' . Str::uuid() . '.' . $outputFormat);

        $command = [
            'ffmpeg',
            '-i', $videoPath,
            '-vn',
            '-acodec', match ($outputFormat) {
                'opus' => 'libopus',
                'aac' => 'aac',
                'mp3' => 'libmp3lame',
                default => 'libopus',
            },
            '-b:a', self::OPUS_BITRATE,
            '-ar', self::SAMPLE_RATE,
            '-af', 'loudnorm=' . self::LOUDNORM_TARGET,
            '-y',
            $outputPath,
        ];

        $commandString = implode(' ', array_map('escapeshellarg', $command));

        exec($commandString . ' 2>&1', $output, $returnCode);

        if ($returnCode !== 0) {
            $this->logger->error('Audio extraction failed', [
                'command' => $commandString,
                'output' => implode("\n", $output),
            ]);
            throw new Exception('Audio extraction failed');
        }

        return $outputPath;
    }

    private function pushToCdn(Media $media): void
    {
        $provider = config('audio-cdn.provider', 'bunny');

        try {
            match ($provider) {
                'bunny' => $this->pushToBunny($media),
                'cloudflare' => $this->pushToCloudflare($media),
                'aws' => $this->pushToAws($media),
                default => $this->logger->info('CDN push skipped - provider not configured'),
            };
        } catch (Exception $e) {
            $this->logger->error('CDN push failed', [
                'media_id' => $media->id,
                'provider' => $provider,
                'error' => $e->getMessage(),
            ]);
            // Don't throw - allow local fallback
        }
    }

    private function pushToBunny(Media $media): void
    {
        $apiKey = config('audio-cdn.bunny.api_key');
        $storageZone = config('audio-cdn.bunny.storage_zone');
        $hostname = config('audio-cdn.bunny.hostname');

        if (!$apiKey || !$storageZone) {
            $this->logger->warning('Bunny CDN not configured');
            return;
        }

        $localPath = $media->getPath();
        $remotePath = 'audio/' . $media->file_name;

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://{$storageZone}.storage.bunnycdn.com/{$remotePath}");
        curl_setopt($ch, CURLOPT_PUT, 1);
        curl_setopt($ch, CURLOPT_INFILE, fopen($localPath, 'rb'));
        curl_setopt($ch, CURLOPT_INFILESIZE, filesize($localPath));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'AccessKey: ' . $apiKey,
            'Content-Type: application/octet-stream',
        ]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode >= 400) {
            throw new Exception("Bunny CDN upload failed: HTTP {$httpCode}");
        }

        // Update media with CDN URL
        $media->update([
            'cdn_url' => "https://{$hostname}/{$remotePath}",
            'cdn_provider' => 'bunny',
        ]);
    }

    private function pushToCloudflare(Media $media): void
    {
        $accountId = config('audio-cdn.cloudflare.account_id');
        $apiToken = config('audio-cdn.cloudflare.api_token');

        if (!$accountId || !$apiToken) {
            $this->logger->warning('Cloudflare Stream not configured');
            return;
        }

        $localPath = $media->getPath();

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://api.cloudflare.com/client/v4/accounts/{$accountId}/stream");
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, [
            'file' => new \CURLFile($localPath),
        ]);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $apiToken,
        ]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode >= 400) {
            throw new Exception("Cloudflare Stream upload failed: HTTP {$httpCode}");
        }

        $data = json_decode($response, true);
        if (!$data['success']) {
            throw new Exception('Cloudflare Stream upload failed: ' . ($data['errors'][0]['message'] ?? 'Unknown error'));
        }

        // Update media with CDN URL
        $media->update([
            'cdn_url' => $data['result']['playback']['hls'],
            'cdn_provider' => 'cloudflare',
        ]);
    }

    private function pushToAws(Media $media): void
    {
        // AWS CloudFront + S3 integration
        // Assumes Media Library is already configured with S3 disk
        $disk = config('media-library.disk_name');
        
        if ($disk !== 's3' && $disk !== 's3-audio') {
            $this->logger->warning('AWS CDN requires S3 disk configuration');
            return;
        }

        // Media Library already handles S3 upload
        // Just set CDN URL if CloudFront is configured
        $cloudFrontDomain = config('audio-cdn.aws.cloudfront_domain');
        
        if ($cloudFrontDomain) {
            $media->update([
                'cdn_url' => "https://{$cloudFrontDomain}/{$media->getPathRelativeToRoot()}",
                'cdn_provider' => 'aws',
            ]);
        }
    }

    private function getBunnySignedUrl(Media $media, int $expiresMinutes): string
    {
        $hostname = config('audio-cdn.bunny.hostname');
        $apiKey = config('audio-cdn.bunny.api_key');

        if (!$hostname || !$apiKey) {
            return $media->getTemporaryUrl(now()->addMinutes($expiresMinutes));
        }

        $path = 'audio/' . $media->file_name;
        $expires = time() + ($expiresMinutes * 60);
        $token = hash_hmac('sha256', $path . $expires, $apiKey);

        return "https://{$hostname}/{$path}?token={$token}&expires={$expires}";
    }

    private function getCloudflareSignedUrl(Media $media, int $expiresMinutes): string
    {
        // Cloudflare Stream signed URLs require JWT
        // For now, return the playback URL (Cloudflare handles access control via account settings)
        return $media->cdn_url ?? $media->getTemporaryUrl(now()->addMinutes($expiresMinutes));
    }

    private function getAwsSignedUrl(Media $media, int $expiresMinutes): string
    {
        return $media->getTemporaryUrl(now()->addMinutes($expiresMinutes));
    }

    public function getAudioMetadata(string $filePath): array
    {
        $command = [
            'ffprobe',
            '-v', 'quiet',
            '-print_format', 'json',
            '-show_format',
            '-show_streams',
            $filePath,
        ];

        $commandString = implode(' ', array_map('escapeshellarg', $command));

        exec($commandString . ' 2>&1', $output, $returnCode);

        if ($returnCode !== 0) {
            throw new Exception('FFprobe failed');
        }

        $data = json_decode(implode("\n", $output), true);

        $audioStream = collect($data['streams'] ?? [])
            ->first(fn ($stream) => ($stream['codec_type'] ?? '') === 'audio');

        if (!$audioStream) {
            throw new Exception('No audio stream found');
        }

        return [
            'duration' => (float) ($audioStream['duration'] ?? 0),
            'bitrate' => (int) ($audioStream['bit_rate'] ?? 0),
            'sample_rate' => (int) ($audioStream['sample_rate'] ?? 0),
            'channels' => (int) ($audioStream['channels'] ?? 0),
            'codec' => $audioStream['codec_name'] ?? 'unknown',
        ];
    }
}
