<?php declare(strict_types=1);

namespace App\Domains\Education\Courses\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\SoftDeletes;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class Course extends Model
{
    protected $table = 'education_courses';

    use HasFactory;

    use HasUuids, SoftDeletes;

        protected $fillable = [
        'uuid',
        'correlation_id',
            'tenant_id',
            'instructor_id',
            'title',
            'description',
            'category',
            'level',
            'price',
            'duration_hours',
            'thumbnail_url',
            'rating',
            'review_count',
            'student_count',
            'status',
            'is_published',
            'correlation_id',
            'tags',
            'metadata',
        ];

        protected $casts = [
            'tags' => 'collection',
            'metadata' => 'json',
            'rating' => 'float',
            'is_published' => 'boolean',
        ];

        protected static function booted(): void
        {
            static::addGlobalScope('tenant', fn ($q) => $q->where('tenant_id', tenant()->id ?? 0));
        }

        public function lessons(): HasMany
        {
            return $this->hasMany(Lesson::class);
        }

        public function enrollments(): HasMany
        {
            return $this->hasMany(Enrollment::class);
        }

        public function reviews(): HasMany
        {
            return $this->hasMany(CourseReview::class);
        }

        public function instructorEarnings(): HasMany
        {
            return $this->hasMany(InstructorEarning::class);
        }

        public function certificates(): HasMany
        {
            return $this->hasMany(Certificate::class);
        }
}
