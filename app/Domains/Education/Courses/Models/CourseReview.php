<?php declare(strict_types=1);

namespace App\Domains\Education\Courses\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class CourseReview extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
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
