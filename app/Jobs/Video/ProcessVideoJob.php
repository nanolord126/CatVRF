<?php

declare(strict_types=1);

namespace App\Jobs\Video;

use App\Events\Video\RecordingCompleted;
use App\Models\Video\VideoRecording;
use App\Services\Video\RecordingService;
use App\Services\Video\VideoOptimizationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

final class ProcessVideoJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    public int $timeout = 1800; // 30 minutes

    public function __construct(
        public readonly VideoRecording $recording
    ) {
        $this->onQueue('video-processing');
    }

    public function handle(RecordingService $recordingService, VideoOptimizationService $optimizationService): void
    {
        try {
            Log::info('Processing video recording', [
                'recording_id' => $this->recording->id,
            ]);

            // Get the raw video file
            $rawPath = $this->recording->getFullStoragePath();
            $disk = $this->recording->storage_disk;

            if (!Storage::disk($disk)->exists($rawPath)) {
                throw new \RuntimeException('Raw video file not found');
            }

            // Download to local temp file for processing
            $tempFile = tempnam(sys_get_temp_dir(), 'video_');
            Storage::disk($disk)->get($rawPath, $tempFile);

            // Get video metadata
            $metadata = $optimizationService->getVideoMetadata($tempFile);

            $this->recording->update([
                'duration_seconds' => $metadata['duration'] ?? 0,
                'width' => $metadata['width'] ?? null,
                'height' => $metadata['height'] ?? null,
                'codec' => $metadata['codec'] ?? null,
            ]);

            // Generate thumbnail
            $thumbnailPath = $optimizationService->generateThumbnail($tempFile, $this->recording->id);
            
            if ($thumbnailPath) {
                $thumbnailStoragePath = "tenant/{$this->recording->tenant_id}/videos/{$this->recording->video_room_id}/thumbnails/" . basename($thumbnailPath);
                Storage::disk($disk)->put($thumbnailStoragePath, file_get_contents($thumbnailPath));
                
                $this->recording->update([
                    'thumbnail_path' => $thumbnailStoragePath,
                    'thumbnail_url' => Storage::disk($disk)->url($thumbnailStoragePath),
                ]);
                
                unlink($thumbnailPath);
            }

            // Convert to HLS with multiple quality variants
            $hlsVariants = $optimizationService->convertToHlsWithVariants(
                $tempFile,
                $this->recording->tenant_id,
                $this->recording->video_room_id,
                $this->recording->id
            );

            // Store HLS variants
            $storedVariants = [];
            foreach ($hlsVariants as $quality => $variantData) {
                $variantStoragePath = "tenant/{$this->recording->tenant_id}/videos/{$this->recording->video_room_id}/hls/{$quality}/";
                
                // Store playlist
                Storage::disk($disk)->put($variantStoragePath . 'playlist.m3u8', $variantData['playlist']);
                
                // Store segments
                foreach ($variantData['segments'] as $segmentFile => $segmentContent) {
                    Storage::disk($disk)->put($variantStoragePath . $segmentFile, $segmentContent);
                }

                $storedVariants[] = [
                    'quality' => $quality,
                    'url' => Storage::disk($disk)->url($variantStoragePath . 'playlist.m3u8'),
                    'bandwidth' => $variantData['bandwidth'],
                    'resolution' => $variantData['resolution'],
                ];
            }

            // Store master playlist
            $masterPlaylist = $optimizationService->generateMasterPlaylist($storedVariants);
            $masterPlaylistPath = "tenant/{$this->recording->tenant_id}/videos/{$this->recording->video_room_id}/hls/master.m3u8";
            Storage::disk($disk)->put($masterPlaylistPath, $masterPlaylist);

            $this->recording->update([
                'hls_variants' => $storedVariants,
                'hls_playlist_url' => Storage::disk($disk)->url($masterPlaylistPath),
            ]);

            // Clean up temp file
            unlink($tempFile);

            // Sync to CDN if enabled
            if (config('cdn.video_enabled', false)) {
                $recordingService->syncToCdn($this->recording);
            }

            // Mark as completed
            $this->recording->markAsCompleted();

            // Broadcast event
            broadcast(new RecordingCompleted($this->recording));

            Log::info('Video recording processed successfully', [
                'recording_id' => $this->recording->id,
                'duration_seconds' => $this->recording->duration_seconds,
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to process video recording', [
                'recording_id' => $this->recording->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $this->recording->markAsFailed($e->getMessage());

            throw $e;
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('Video processing job failed', [
            'recording_id' => $this->recording->id,
            'error' => $exception->getMessage(),
        ]);

        $this->recording->markAsFailed($exception->getMessage());
    }
}
