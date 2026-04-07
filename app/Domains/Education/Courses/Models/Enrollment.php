<?php declare(strict_types=1);

namespace App\Domains\Education\Courses\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class Enrollment extends Model
{
    protected $table = 'education_enrollments';

    use HasFactory;

    use HasUuids, SoftDeletes;

        protected $fillable = [
        'uuid',
        'correlation_id',
            'tenant_id',
            'course_id',
            'student_id',
            'status',
            'progress_percent',
            'enrolled_at',
            'completed_at',
            'last_accessed_at',
            'total_watch_time_seconds',
            'course_price',
            'commission_price',
            'correlation_id',
            'tags',
        ];

        protected $casts = [
            'enrolled_at' => 'datetime',
            'completed_at' => 'datetime',
            'last_accessed_at' => 'datetime',
            'tags' => 'collection',
        ];

        public function booted(): void
        {
            static::addGlobalScope('tenant', fn ($q) => $q->where('tenant_id', tenant()->id ?? 0));
        }

        public function course(): BelongsTo
        {
            return $this->belongsTo(Course::class);
        }

        public function lessonProgress(): HasMany
        {
            return $this->hasMany(LessonProgress::class);
        }

        public function certificate(): BelongsTo
        {
            return $this->belongsTo(Certificate::class);
        }

        public function review(): BelongsTo
        {
            return $this->belongsTo(CourseReview::class);
        }
}
