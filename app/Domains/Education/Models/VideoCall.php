<?php

declare(strict_types=1);

namespace App\Domains\Education\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * КАНОН 2026: Модель видеозвонка (WebRTC в Education).
 * Учет живых занятий и доступа.
 */
final class VideoCall extends Model
{
    protected $table = 'video_calls';

    protected $fillable = [
        'uuid',
        'tenant_id',
        'lesson_id',
        'teacher_id',
        'room_id',
        'scheduled_at',
        'started_at',
        'ended_at',
        'status',
        'participants_logs',
        'correlation_id',
    ];

    protected $casts = [
        'uuid' => 'string',
        'scheduled_at' => 'datetime',
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
        'participants_logs' => 'json',
        'status' => 'string',
    ];

    protected $hidden = [
        'id',
        'tenant_id',
    ];

    /**
     * КАНОН 2026: Изоляция тенанта и UUID
     */
    protected static function booted(): void
    {
        static::addGlobalScope('tenant', function ($builder) {
            if (auth()->check()) {
                $builder->where('tenant_id', tenant()->id);
            }
        });

        static::creating(function (VideoCall $videoCall) {
            $videoCall->uuid = $videoCall->uuid ?? (string) Str::uuid();
            $videoCall->tenant_id = $videoCall->tenant_id ?? (int) tenant()->id;
            $videoCall->room_id = $videoCall->room_id ?? (string) Str::uuid();
            $videoCall->correlation_id = $videoCall->correlation_id ?? (string) Str::uuid();
        });
    }

    /**
     * Преподаватель сессии
     */
    public function teacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class);
    }

    /**
     * Урок, к которому привязан звонок
     */
    public function lesson(): BelongsTo
    {
        return $this->belongsTo(Lesson::class);
    }

    /**
     * Получение статус-линка для WebRTC комнаты
     */
    public function getWebRtcUrlAttribute(): string
    {
        return config('services.webrtc.base_url') . '/join/' . $this->room_id;
    }
}
