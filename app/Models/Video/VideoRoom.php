<?php

declare(strict_types=1);

namespace App\Models\Video;

use App\Models\BaseDomainModel;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

final class VideoRoom extends BaseDomainModel
{
    use SoftDeletes;

    protected $table = 'video_rooms';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'id',
        'tenant_id',
        'type',
        'status',
        'host_id',
        'host_type',
        'pet_id',
        'pet_type',
        'room_name',
        'description',
        'scheduled_at',
        'started_at',
        'ended_at',
        'duration_seconds',
        'recording_enabled',
        'recording_consent_given',
        'recording_consent_signature',
        'recording_consent_given_at',
        'recording_url',
        'recording_path',
        'recording_size_bytes',
        'recording_expires_at',
        'livekit_room_id',
        'livekit_room_name',
        'access_token',
        'access_token_expires_at',
        'is_public',
        'max_participants',
        'metadata',
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
        'recording_consent_given_at' => 'datetime',
        'recording_expires_at' => 'datetime',
        'access_token_expires_at' => 'datetime',
        'recording_enabled' => 'boolean',
        'recording_consent_given' => 'boolean',
        'is_public' => 'boolean',
        'duration_seconds' => 'integer',
        'recording_size_bytes' => 'integer',
        'max_participants' => 'integer',
        'metadata' => 'json',
    ];

    protected static function booted(): void
    {
        parent::booted();

        static::creating(function (self $model): void {
            if (empty($model->id)) {
                $model->id = (string) Str::uuid();
            }
            if (empty($model->livekit_room_id)) {
                $model->livekit_room_id = (string) Str::uuid();
            }
            if (empty($model->livekit_room_name)) {
                $model->livekit_room_name = 'room_' . $model->id;
            }
        });
    }

    // ========================
    // RELATIONSHIPS
    // ========================

    public function participants(): HasMany
    {
        return $this->hasMany(VideoParticipant::class, 'video_room_id');
    }

    public function recordings(): HasMany
    {
        return $this->hasMany(VideoRecording::class, 'video_room_id');
    }

    public function host(): MorphTo
    {
        return $this->morphTo();
    }

    public function pet(): MorphTo
    {
        return $this->morphTo();
    }

    // ========================
    // SCOPES
    // ========================

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeScheduled($query)
    {
        return $query->where('status', 'scheduled');
    }

    public function scopeEnded($query)
    {
        return $query->where('status', 'ended');
    }

    public function scopeWithRecording($query)
    {
        return $query->where('recording_enabled', true);
    }

    public function scopePublic($query)
    {
        return $query->where('is_public', true);
    }

    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    // ========================
    // METHODS
    // ========================

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function isScheduled(): bool
    {
        return $this->status === 'scheduled';
    }

    public function isEnded(): bool
    {
        return $this->status === 'ended';
    }

    public function canStart(): bool
    {
        return $this->status === 'scheduled' && $this->scheduled_at?->isPast();
    }

    public function start(): bool
    {
        return $this->update([
            'status' => 'active',
            'started_at' => now(),
        ]);
    }

    public function end(): bool
    {
        $duration = $this->started_at ? now()->diffInSeconds($this->started_at) : 0;

        return $this->update([
            'status' => 'ended',
            'ended_at' => now(),
            'duration_seconds' => $duration,
        ]);
    }

    public function hasRecordingConsent(): bool
    {
        return $this->recording_consent_given && $this->recording_consent_given_at !== null;
    }

    public function giveRecordingConsent(string $signature): bool
    {
        return $this->update([
            'recording_consent_given' => true,
            'recording_consent_signature' => $signature,
            'recording_consent_given_at' => now(),
        ]);
    }

    public function generateAccessToken(int $expiresInMinutes = 60): string
    {
        $token = Str::random(64);

        $this->update([
            'access_token' => hash('sha256', $token),
            'access_token_expires_at' => now()->addMinutes($expiresInMinutes),
        ]);

        return $token;
    }

    public function isAccessTokenValid(?string $token): bool
    {
        if (!$token || !$this->access_token) {
            return false;
        }

        return hash('sha256', $token) === $this->access_token
            && $this->access_token_expires_at?->isFuture();
    }

    public function getActiveParticipantsCount(): int
    {
        return $this->participants()->where('status', 'joined')->count();
    }

    public function isFull(): bool
    {
        return $this->getActiveParticipantsCount() >= $this->max_participants;
    }
}
