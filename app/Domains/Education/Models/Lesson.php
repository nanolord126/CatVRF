<?php

declare(strict_types=1);

namespace App\Domains\Education\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * КАНОН 2026: Модель урока (Education).
 * Изоляция тенантов, UUID, типы контента.
 */
final class Lesson extends Model
{
    protected $table = 'lessons';

    protected $fillable = [
        'uuid',
        'course_id',
        'title',
        'type',
        'content',
        'meeting_url',
        'duration_minutes',
        'order',
        'correlation_id',
    ];

    protected $casts = [
        'uuid' => 'string',
        'duration_minutes' => 'integer',
        'order' => 'integer',
        'type' => 'string',
    ];

    protected $hidden = [
        'id',
    ];

    /**
     * КАНОН 2026: Инициализация UUID
     */
    protected static function booted(): void
    {
        static::creating(function (Lesson $lesson) {
            $lesson->uuid = $lesson->uuid ?? (string) Str::uuid();
            $lesson->correlation_id = $lesson->correlation_id ?? (string) Str::uuid();
        });
    }

    /**
     * Модуль курса, к которому относится урок
     */
    public function module(): BelongsTo
    {
        return $this->belongsTo(CourseModule::class, 'course_module_id');
    }

    /**
     * Живые видеозвонки в рамках урока
     */
    public function videoCalls(): HasMany
    {
        return $this->hasMany(VideoCall::class);
    }

    /**
     * Дочерний метод получения тенанта через связи (для безопасности)
     */
    public function getTenantIdAttribute(): int
    {
        return (int) $this->module->course->tenant_id;
    }
}
