<?php

declare(strict_types=1);

namespace App\Traits;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

trait HasOptimizedAudio
{
    public function addAudioReport(UploadedFile $audioFile, string $collection = 'audio_reports'): Media
    {
        $service = app(\App\Services\Audio\AudioOptimizationService::class);

        return $service->optimizeAndUpload($audioFile, $this, $collection, 'opus');
    }

    public function addVoiceRecording(UploadedFile $audioFile, string $collection = 'voice_recordings'): Media
    {
        $service = app(\App\Services\Audio\AudioOptimizationService::class);

        return $service->optimizeAndUpload($audioFile, $this, $collection, 'opus');
    }

    public function getOptimizedAudioUrl(?Media $media = null, int $expiresMinutes = 1440): ?string
    {
        if (!$media) {
            return null;
        }

        $service = app(\App\Services\Audio\AudioOptimizationService::class);

        return $service->getSecureCdnUrl($media, $expiresMinutes);
    }

    public function getAudioPlayerUrl(Media $media): string
    {
        // Prefer CDN URL if available
        if ($media->cdn_url) {
            return $media->cdn_url;
        }

        // Fallback to local URL
        return $media->getUrl();
    }

    public function processExistingAudio(Media $media): void
    {
        $service = app(\App\Services\Audio\AudioOptimizationService::class);
        $service->processRecording($media);
    }

    public function getAudioMetadata(Media $media): ?array
    {
        $service = app(\App\Services\Audio\AudioOptimizationService::class);

        try {
            $path = $media->getPath();
            if (!file_exists($path)) {
                return null;
            }

            return $service->getAudioMetadata($path);
        } catch (\Exception $e) {
            return null;
        }
    }

    public function hasOptimizedAudio(): bool
    {
        return $this->media()
            ->where('collection_name', 'audio_optimized')
            ->exists();
    }

    public function getOptimizedAudio(): ?Media
    {
        return $this->media()
            ->where('collection_name', 'audio_optimized')
            ->latest()
            ->first();
    }

    /**
     * Get all audio recordings for this model
     */
    public function audioRecordings()
    {
        return $this->media()->where('collection_name', 'audio_reports');
    }

    /**
     * Get all voice recordings for this model
     */
    public function voiceRecordings()
    {
        return $this->media()->where('collection_name', 'voice_recordings');
    }

    /**
     * Get all video recordings (for audio extraction)
     */
    public function videoRecordings()
    {
        return $this->media()->where('collection_name', 'video_recordings');
    }
}
