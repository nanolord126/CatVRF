<?php

declare(strict_types=1);

namespace App\Jobs;

use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

final class ProcessAudioJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 300; // 5 minutes

    public function __construct(
        public readonly Media $media,
        public readonly string $format = 'opus',
        public readonly bool $pushToCdn = true,
    ) {
        $this->onQueue('audio-processing');
    }

    public function handle(): void
    {
        try {
            $service = app(\App\Services\Audio\AudioOptimizationService::class);

            // Extract audio if this is a video file
            if ($this->isVideoFile($this->media->mime_type)) {
                $this->processVideoRecording($service);
            } else {
                $this->processAudioFile($service);
            }

            Log::info('Audio processing completed', [
                'media_id' => $this->media->id,
                'format' => $this->format,
            ]);
        } catch (Exception $e) {
            Log::error('Audio processing failed', [
                'media_id' => $this->media->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            $this->media->update([
                'processing_status' => 'failed',
                'processing_error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    private function isVideoFile(?string $mimeType): bool
    {
        if (!$mimeType) {
            return false;
        }

        return str_starts_with($mimeType, 'video/');
    }

    private function processVideoRecording(\App\Services\Audio\AudioOptimizationService $service): void
    {
        $this->media->update(['processing_status' => 'processing']);

        $localPath = $this->media->getPath();
        
        if (!file_exists($localPath)) {
            throw new Exception('Source video file not found');
        }

        // Extract audio from video
        $audioPath = $service->extractAudioFromVideo($localPath, $this->format);

        // Get metadata
        $metadata = $service->getAudioMetadata($audioPath);

        // Create new media for optimized audio
        $optimizedMedia = $this->media->model->addMedia($audioPath)
            ->usingFileName(Str::uuid() . '.' . $this->format)
            ->withCustomProperties([
                'extracted_from' => $this->media->id,
                'duration' => $metadata['duration'],
                'bitrate' => $metadata['bitrate'],
                'sample_rate' => $metadata['sample_rate'],
                'codec' => $metadata['codec'],
            ])
            ->toMediaCollection('audio_optimized');

        // Push to CDN if enabled
        if ($this->pushToCdn) {
            $service->pushToCdn($optimizedMedia);
        }

        // Update original media with reference
        $this->media->update([
            'processing_status' => 'completed',
            'optimized_audio_id' => $optimizedMedia->id,
            'processing_completed_at' => now(),
        ]);

        // Clean up temp file
        if (file_exists($audioPath)) {
            unlink($audioPath);
        }
    }

    private function processAudioFile(\App\Services\Audio\AudioOptimizationService $service): void
    {
        $this->media->update(['processing_status' => 'processing']);

        $localPath = $this->media->getPath();
        
        if (!file_exists($localPath)) {
            throw new Exception('Source audio file not found');
        }

        // Optimize audio
        $optimizedPath = $service->runFfmpegOptimization(
            new \Illuminate\Http\UploadedFile($localPath, basename($localPath)),
            $this->format
        );

        // Get metadata
        $metadata = $service->getAudioMetadata($optimizedPath);

        // Create new media for optimized audio
        $optimizedMedia = $this->media->model->addMedia($optimizedPath)
            ->usingFileName(Str::uuid() . '.' . $this->format)
            ->withCustomProperties([
                'original_id' => $this->media->id,
                'duration' => $metadata['duration'],
                'bitrate' => $metadata['bitrate'],
                'sample_rate' => $metadata['sample_rate'],
                'codec' => $metadata['codec'],
            ])
            ->toMediaCollection('audio_optimized');

        // Push to CDN if enabled
        if ($this->pushToCdn) {
            $service->pushToCdn($optimizedMedia);
        }

        // Update original media with reference
        $this->media->update([
            'processing_status' => 'completed',
            'optimized_audio_id' => $optimizedMedia->id,
            'processing_completed_at' => now(),
        ]);

        // Clean up temp file
        if (file_exists($optimizedPath)) {
            unlink($optimizedPath);
        }
    }

    public function failed(Exception $exception): void
    {
        Log::error('Audio processing job failed', [
            'media_id' => $this->media->id,
            'exception' => $exception->getMessage(),
        ]);

        $this->media->update([
            'processing_status' => 'failed',
            'processing_error' => $exception->getMessage(),
            'processing_failed_at' => now(),
        ]);
    }
}
