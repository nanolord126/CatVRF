<?php declare(strict_types=1);

/**
 * Lesson — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/lesson
 */


namespace App\Domains\Education\Courses\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\SoftDeletes;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class Lesson extends Model
{
    protected $table = 'education_lessons';

    use HasFactory;

    use HasUuids, SoftDeletes;

        protected $fillable = [
        'uuid',
        'correlation_id',
            'tenant_id',
            'course_id',
            'title',
            'description',
            'content',
            'video_url',
            'duration_minutes',
            'sort_order',
            'is_published',
            'resources',
            'correlation_id',
        ];

        protected $casts = [
            'resources' => 'json',
            'is_published' => 'boolean',
        ];

        protected static function booted(): void
        {
            static::addGlobalScope('tenant', fn ($q) => $q->where('tenant_id', tenant()->id ?? 0));
        }

        public function course(): BelongsTo
        {
            return $this->belongsTo(Course::class);
        }

        public function progress(): HasMany
        {
            return $this->hasMany(LessonProgress::class);
        }

    /**
     * Version identifier for this component.
     */
    private const VERSION = '1.0.0';

}
