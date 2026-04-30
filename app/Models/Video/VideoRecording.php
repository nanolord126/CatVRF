<?php

declare(strict_types=1);

namespace App\Models\Video;

use App\Models\BaseDomainModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

final class VideoRecording extends BaseDomainModel
{
    use SoftDeletes;

    protected $table = 'video_recordings';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'id',
        'tenant_id',
        'video_room_id',
        'title',
        'description',
        'storage_disk',
        'path',
        'filename',
        'size_bytes',
        'mime_type',
        'processing_status',
        'processing_error',
        'duration_seconds',
        'width',
        'height',
        'codec',
        'cdn_provider',
        'cdn_id',
        'cdn_url',
        'cdn_synced',
        'hls_variants',
        'hls_playlist_url',
        'thumbnail_url',
        'thumbnail_path',
        'is_public',
        'available_until',
        'consent_given',
        'consent_signature',
        'consent_given_at',
        'watermark_enabled',
        'watermark_text',
    ];

    protected $casts = [
        'size_bytes' => 'integer',
        'duration_seconds' => 'integer',
        'width' => 'integer',
        'height' => 'integer',
        'cdn_synced' => 'boolean',
        'is_public' => 'boolean',
        'consent_given' => 'boolean',
        'watermark_enabled' => 'boolean',
        'available_until' => 'datetime',
        'consent_given_at' => 'datetime',
        'hls_variants' => 'json',
    ];

    protected static function booted(): void
    {
        parent::booted();

        static::creating(function (self $model): void {
            if (empty($model->id)) {
                $model->id = (string) Str::uuid();
            }
            if (empty($model->storage_disk)) {
                $model->storage_disk = config('filesystems.default', 's3');
            }
        });
    }

    // ========================
    // RELATIONSHIPS
    // ========================

    public function room(): BelongsTo
    {
        return $this->belongsTo(VideoRoom::class, 'video_room_id');
    }

    // ========================
    // SCOPES
    // ========================

    public function scopePending($query)
    {
        return $query->where('processing_status', 'pending');
    }

    public function scopeProcessing($query)
    {
        return $query->where('processing_status', 'processing');
    }

    public function scopeCompleted($query)
    {
        return $query->where('processing_status', 'completed');
    }

    public function scopeFailed($query)
    {
        return $query->where('processing_status', 'failed');
    }

    public function scopePublic($query)
    {
        return $query->where('is_public', true);
    }

    public function scopeAvailable($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('available_until')
                ->orWhere('available_until', '>', now());
        });
    }

    public function scopeWithConsent($query)
    {
        return $query->where('consent_given', true);
    }

    // ========================
    // METHODS
    // ========================

    public function isPending(): bool
    {
        return $this->processing_status === 'pending';
    }

    public function isProcessing(): bool
    {
        return $this->processing_status === 'processing';
    }

    public function isCompleted(): bool
    {
        return $this->processing_status === 'completed';
    }

    public function isFailed(): bool
    {
        return $this->processing_status === 'failed';
    }

    public function isAvailable(): bool
    {
        return $this->available_until === null || $this->available_until->isFuture();
    }

    public function markAsProcessing(): bool
    {
        return $this->update(['processing_status' => 'processing']);
    }

    public function markAsCompleted(): bool
    {
        return $this->update(['processing_status' => 'completed']);
    }

    public function markAsFailed(string $error): bool
    {
        return $this->update([
            'processing_status' => 'failed',
            'processing_error' => $error,
        ]);
    }

    public function hasConsent(): bool
    {
        return $this->consent_given && $this->consent_given_at !== null;
    }

    public function giveConsent(string $signature): bool
    {
        return $this->update([
            'consent_given' => true,
            'consent_signature' => $signature,
            'consent_given_at' => now(),
        ]);
    }

    public function getFullStoragePath(): string
    {
        return $this->path . '/' . $this->filename;
    }

    public function getSizeInMB(): float
    {
        return round($this->size_bytes / 1024 / 1024, 2);
    }

    public function getDurationInMinutes(): float
    {
        return round($this->duration_seconds / 60, 2);
    }

    public function isCdnSynced(): bool
    {
        return $this->cdn_synced && $this->cdn_url !== null;
    }

    public function getPlaybackUrl(): ?string
    {
        if ($this->isCdnSynced()) {
            return $this->cdn_url;
        }

        if ($this->hls_playlist_url) {
            return $this->hls_playlist_url;
        }

        return null;
    }

    public function setAvailabilityDays(int $days): bool
    {
        return $this->update([
            'available_until' => now()->addDays($days),
        ]);
    }

    public function setWatermark(string $text): bool
    {
        return $this->update([
            'watermark_enabled' => true,
            'watermark_text' => $text,
        ]);
    }
}
