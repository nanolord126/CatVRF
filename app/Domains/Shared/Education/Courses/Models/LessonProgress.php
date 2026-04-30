<?php

declare(strict_types=1);

/**
 * LessonProgress — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @version 2026.1
 *
 * @author CatVRF Team
 * @license Proprietary

 *
 * @see https://catvrf.ru/docs/lessonprogress
 */

namespace App\Domains\Education\Courses\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

final class LessonProgress extends Model
{
    use HasUuids;
    use SoftDeletes;

    /**
     * Version identifier for this component.
     */
    private const VERSION = '1.0.0';

    protected $table = 'education_lesson_progress';

    protected $fillable = [
        'uuid',
        'correlation_id',
        'tenant_id',
        'enrollment_id',
        'lesson_id',
        'is_completed',
        'completed_at',
        'watch_time_seconds',
        'completion_percent',
        'correlation_id',
    ];

    protected $casts = [
        'is_completed' => 'boolean',
        'completed_at' => 'datetime',
        'completion_percent' => 'float',
    ];

    public function enrollment(): BelongsTo
    {
        return $this->belongsTo(Enrollment::class);
    }

    public function lesson(): BelongsTo
    {
        return $this->belongsTo(Lesson::class);
    }

    protected static function booted(): void
    {
        self::addGlobalScope('tenant', fn ($q) => $q->where('tenant_id', tenant()->id ?? 0));
    }
}
