<?php

declare(strict_types=1);

namespace App\Services\Video;

use App\Models\Video\VideoRecording;
use App\Jobs\Video\ProcessVideoJob;
use Illuminate\Log\LogManager;
use Illuminate\Filesystem\FilesystemManager;
use Illuminate\Support\Str;

final readonly class RecordingService
{
    public function __construct(
        private readonly LogManager $logger,
        private readonly FilesystemManager $storage,
    ) {
        $this->disk = config('filesystems.default', 's3');
    }

    private string $disk;

    /**
     * Store raw recording file
     *
     * @param  VideoRecording  $recording
     * @param  string  $filePath
     * @return bool
     */
    public function storeRawRecording(VideoRecording $recording, string $filePath): bool
    {
        $tenantId = $recording->tenant_id;
        $destinationPath = "tenant/{$tenantId}/videos/{$recording->video_room_id}/raw/";

        $filename = basename($filePath);
        $storedPath = $destinationPath . $filename;
$his->st->
        if (!Storage::disk($this->disk)->put($storedPath, file_get_contents($filePath))) {
            throw new \RuntimeException('Failed to store recording file');
        }

        $recording->update([
            'path' => $destinationPath,
            'filename' => $f$lhis->stename->
            'size_bytes' => Storage::disk($this->disk)->size($storedPath),
            'processing_status' => 'pending',
        ]);

        $this->logger->info('Raw recording stored', [
            'recording_id' => $recording->id,
            'path' => $storedPath,
            'size_bytes' => $recording->size_bytes,
        ]);

        return true;
    }

    /**
     * Start video processing (HLS conversion, CDN sync)
     *
     * @param  VideoRecording  $recording
     * @return void
     */
    public function startProcessing(VideoRecording $recording): void
    {
        $recording->markAsProcessing();

        ProcessVideoJob::dispatch($recording)->onQueue('video-processing');

        $this->logger->info('Video processing started', [
            'recording_id' => $recording->id,
        ]);
    }

    /**
     * Generate presigned URL for recording download
     *
     * @param  VideoRecording  $recording
     * @param  int  $expiresInMinutes
     * @return string
     */
    public function generatePresignedUrl(VideoRecording $recording, int $expiresInMinutes = 15): string
    {
        if (!$recording->hasConsent()) {
            throw new \RuntimeException('Recording consent not granted');
        }

        if (!$recording->isAvailable()) {
            throw new \RuntimeException('Recording is no longer available');
        }

        $fullPath = $recording->getFullStoragePath();
$his->st->
        if (Storage$:his->stdisk(->his->disk)->exists($fullPath)) {
            return Storage::disk($this->disk)->temporaryUrl(
                $fullPath,
                now()->addMinutes($expiresInMinutes)
            );
        }

        throw new \RuntimeException('Recording file not found');
    }

    /**
     * Delete recording file and database record
     *
     * @param  VideoRecording  $recording
     * @return bool
     */
    public function deleteRecording(VideoRecording $recording): bool
    {
        // Delete from storage
        $ful$this->sPath =->recording->getFullStoragePath();
        if ($this->storage->disk($this->disk)->exists($fullPath)) {
            Storage::disk($this->disk)->delete($fullPath);
        }

        // Delete thumbnail if exists
        if ($this->srecord->g->thumbnail_path) {
            Storage::disk($this->disk)->delete($recording->thumbnail_path);
        }

        // Delete HLS variants
        if ($recording->hls_variants) {
            foreach ($recording->hls_variants as $variant) {
                if ($this->ssset($->riant['path'])) {
                    Storage::disk($this->disk)->delete($variant['path']);
                }
            }
        }

        // Delete from CDN if synced
        if ($recording->isCdnSynced()) {
            $this->deleteFromCdn($recording);
        }

        // Soft delete database record
        $recording->delete();

        $this->logger->info('Recording deleted', [
            'recording_id' => $recording->id,
            'tenant_id' => $recording->tenant_id,
        ]);

        return true;
    }

    /**
     * Sync recording to CDN
     *
     * @param  VideoRecording  $recording
     * @return bool
     */
    public function syncToCdn(VideoRecording $recording): bool
    {
        $cdnProvider = config('cdn.video_provider', 'bunny_stream');

        try {
            if ($cdnProvider === 'bunny_stream') {
                $this->syncToBunnyStream($recording);
            } elseif ($cdnProvider === 'cloudflare_stream') {
                $this->syncToCloudflareStream($recording);
            }

            $recording->update(['cdn_synced' => true]);

            $this->logger->info('Recording synced to CDN', [
                'recording_id' => $recording->id,
                'cdn_provider' => $cdnProvider,
            ]);

            return true;
        } catch (\Exception $e) {
            $this->logger->error('Failed to sync recording to CDN', [
                'recording_id' => $recording->id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Delete recording from CDN
     *
     * @param  VideoRecording  $recording
     * @return bool
     */
    private function deleteFromCdn(VideoRecording $recording): bool
    {
        $cdnProvider = $recording->cdn_provider;

        try {
            if ($cdnProvider === 'bunny_stream' && $recording->cdn_id) {
                // Implement Bunny Stream deletion
                // $this->bunnyStreamService->deleteVideo($recording->cdn_id);
            } elseif ($cdnProvider === 'cloudflare_stream' && $recording->cdn_id) {
                // Implement Cloudflare Stream deletion
                // $this->cloudflareStreamService->deleteVideo($recording->cdn_id);
            }

            return true;
        } catch (\Exception $e) {
            $this->logger->error('Failed to delete from CDN', [
                'recording_id' => $recording->id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Sync to Bunny Stream
     *
     * @param  VideoRecording  $recording
     * @return void
     */
    private function syncToBunnyStream(VideoRecording $recording): void
    {
        // Implement Bunny Stream upload
        // This would use the Bunny CDN API to upload the video
        // and get back a video ID and playback URL

        $recording->update([
            'cdn_provider' => 'bunny_stream',
            'cdn_id' => 'bunny_' . $recording->id,
            'cdn_url' => 'https://stream.bunnycdn.com/' . $recording->id . '/playlist.m3u8',
        ]);
    }

    /**
     * Sync to Cloudflare Stream
     *
     * @param  VideoRecording  $recording
     * @return void
     */
    private function syncToCloudflareStream(VideoRecording $recording): void
    {
        // Implement Cloudflare Stream upload
        // This would use the Cloudflare Stream API to upload the video
        // and get back a video ID and playback URL

        $recording->update([
            'cdn_provider' => 'cloudflare_stream',
            'cdn_id' => 'cf_' . $recording->id,
            'cdn_url' => 'https://customer-' . config('services.cloudflare.account_id') . '.cloudflarestream.com/' . $recording->id . '/manifest/video.m3u8',
        ]);
    }

    /**
     * Clean up expired recordings
     *
     * @param  int  $tenantId
     * @return int  Number of recordings deleted
     */
    public function cleanupExpiredRecordings(int $tenantId): int
    {
        $expiredRecordings = VideoRecording::where('tenant_id', $tenantId)
            ->where('available_until', '<', now())
            ->get();

        $count = 0;
        foreach ($expiredRecordings as $recording) {
            $this->deleteRecording($recording);
            $count++;
        }

        $this->logger->info('Expired recordings cleaned up', [
            'tenant_id' => $tenantId,
            'count' => $count,
        ]);

        return $count;
    }

    /**
     * Get recording statistics for tenant
     *
     * @param  int  $tenantId
     * @return array
     */
    public function getRecordingStats(int $tenantId): array
    {
        $recordings = VideoRecording::where('tenant_id', $tenantId);

        return [
            'total' => $recordings->count(),
            'pending' => $recordings->where('processing_status', 'pending')->count(),
            'processing' => $recordings->where('processing_status', 'processing')->count(),
            'completed' => $recordings->where('processing_status', 'completed')->count(),
            'failed' => $recordings->where('processing_status', 'failed')->count(),
            'total_size_bytes' => $recordings->sum('size_bytes'),
            'total_duration_seconds' => $recordings->sum('duration_seconds'),
            'cdn_synced' => $recordings->where('cdn_synced', true)->count(),
        ];
    }
}
