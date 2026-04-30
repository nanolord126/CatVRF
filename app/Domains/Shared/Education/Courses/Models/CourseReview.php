<?php

declare(strict_types=1);

namespace App\Domains\Education\Courses\Models;

use Carbon\CarbonImmutable;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

final class CourseReview extends Model
{
    use HasUuids;
    use SoftDeletes;

    protected $table = 'education_course_reviews';

    protected $fillable = [
        'uuid',
        'correlation_id',
        'tenant_id',
        'course_id',
        'student_id',
        'enrollment_id',
        'rating',
        'title',
        'content',
        'categories',
        'helpful_count',
        'unhelpful_count',
        'verified_purchase',
        'published_at',
        'correlation_id',
        'tags',
    ];

    protected $casts = [
        'categories' => 'json',
        'published_at' => 'datetime',
        'verified_purchase' => 'boolean',
        'tags' => 'collection',
    ];

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function enrollment(): BelongsTo
    {
        return $this->belongsTo(Enrollment::class);
    }

    /**
     * Get the string representation of this instance.
     *
     * @return string The string representation
     */
    public function __toString(): string
    {
        return self::class;
    }

    /**
     * Get debug information for this instance.
     *
     * @return array<string, mixed> Debug data including class name and state
     */
    public function toDebugArray(): array
    {
        return [
            'class' => self::class,
            'timestamp' => CarbonImmutable::now()->toIso8601String(),
        ];
    }

    protected static function booted(): void
    {
        self::addGlobalScope('tenant', fn ($q) => $q->where('tenant_id', tenant()->id ?? 0));
    }
}
