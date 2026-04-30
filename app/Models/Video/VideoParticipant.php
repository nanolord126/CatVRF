<?php

declare(strict_types=1);

namespace App\Models\Video;

use App\Models\BaseDomainModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Str;

final class VideoParticipant extends BaseDomainModel
{
    protected $table = 'video_participants';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'id',
        'tenant_id',
        'video_room_id',
        'user_id',
        'user_type',
        'role',
        'status',
        'livekit_participant_identity',
        'joined_at',
        'left_at',
        'duration_seconds',
        'device_type',
        'browser',
        'ip_address',
        'metadata',
    ];

    protected $casts = [
        'joined_at' => 'datetime',
        'left_at' => 'datetime',
        'duration_seconds' => 'integer',
        'metadata' => 'json',
    ];

    protected static function booted(): void
    {
        parent::booted();

        static::creating(function (self $model): void {
            if (empty($model->id)) {
                $model->id = (string) Str::uuid();
            }
            if (empty($model->livekit_participant_identity)) {
                $model->livekit_participant_identity = 'participant_' . $model->id;
            }
        });

        static::updating(function (self $model): void {
            if ($model->isDirty('status') && $model->status === 'joined' && !$model->joined_at) {
                $model->joined_at = now();
            }
            if ($model->isDirty('status') && $model->status === 'left' && !$model->left_at && $model->joined_at) {
                $model->left_at = now();
                $model->duration_seconds = now()->diffInSeconds($model->joined_at);
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

    public function user(): MorphTo
    {
        return $this->morphTo();
    }

    // ========================
    // SCOPES
    // ========================

    public function scopeJoined($query)
    {
        return $query->where('status', 'joined');
    }

    public function scopeLeft($query)
    {
        return $query->where('status', 'left');
    }

    public function scopeHosts($query)
    {
        return $query->where('role', 'host');
    }

    public function scopeCoHosts($query)
    {
        return $query->where('role', 'co_host');
    }

    public function scopeViewers($query)
    {
        return $query->where('role', 'viewer');
    }

    // ========================
    // METHODS
    // ========================

    public function isHost(): bool
    {
        return $this->role === 'host';
    }

    public function isCoHost(): bool
    {
        return $this->role === 'co_host';
    }

    public function isViewer(): bool
    {
        return $this->role === 'viewer';
    }

    public function hasJoined(): bool
    {
        return $this->status === 'joined';
    }

    public function hasLeft(): bool
    {
        return $this->status === 'left';
    }

    public function join(): bool
    {
        return $this->update([
            'status' => 'joined',
            'joined_at' => now(),
            'ip_address' => request()->ip(),
            'device_type' => $this->detectDeviceType(),
            'browser' => $this->detectBrowser(),
        ]);
    }

    public function leave(): bool
    {
        $duration = $this->joined_at ? now()->diffInSeconds($this->joined_at) : 0;

        return $this->update([
            'status' => 'left',
            'left_at' => now(),
            'duration_seconds' => $duration,
        ]);
    }

    private function detectDeviceType(): string
    {
        $userAgent = request()->userAgent();

        if (preg_match('/mobile/i', $userAgent)) {
            return 'mobile';
        }

        if (preg_match('/tablet/i', $userAgent)) {
            return 'tablet';
        }

        return 'desktop';
    }

    private function detectBrowser(): string
    {
        $userAgent = request()->userAgent();

        if (preg_match('/chrome/i', $userAgent)) {
            return 'Chrome';
        }

        if (preg_match('/firefox/i', $userAgent)) {
            return 'Firefox';
        }

        if (preg_match('/safari/i', $userAgent)) {
            return 'Safari';
        }

        if (preg_match('/edge/i', $userAgent)) {
            return 'Edge';
        }

        return 'Unknown';
    }
}
