<?php

declare(strict_types=1);


namespace App\Domains\Education\Courses\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

final /**
 * CourseReview
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class CourseReview extends Model
{
    use HasUuids, SoftDeletes;

    protected $fillable = [
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

    public function booted(): void
    {
        static::addGlobalScope('tenant', fn ($q) => $q->where('tenant_id', tenant('id') ?? 0));
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function enrollment(): BelongsTo
    {
        return $this->belongsTo(Enrollment::class);
    }
}
