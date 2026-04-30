<?php

declare(strict_types=1);

namespace App\Domains\Staff\Domain\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * StaffCourse — курс обучения.
 * CatVRF 2026 — PRODUCTION MANDATORY.
 */
final class StaffCourse extends Model
{
    use SoftDeletes;

    protected $table = 'staff_courses';

    protected $fillable = [
        'uuid',
        'tenant_id',
        'title',
        'description',
        'category',
        'difficulty_level',
        'content',
        'modules',
        'thumbnail_url',
        'duration_minutes',
        'max_participants',
        'price',
        'status',
    ];

    protected $casts = [
        'modules' => 'json',
        'duration_minutes' => 'integer',
        'max_participants' => 'integer',
        'price' => 'decimal:2',
    ];

    protected $hidden = [
        'deleted_at',
    ];

    public function enrollments(): HasMany
    {
        return $this->hasMany(StaffCourseEnrollment::class);
    }

    public function quizzes(): HasMany
    {
        return $this->hasMany(StaffQuiz::class);
    }

    // Scopes
    public function scopePublished($query)
    {
        return $query->where('status', 'published');
    }

    public function scopeByCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    public function scopeByDifficulty($query, string $level)
    {
        return $query->where('difficulty_level', $level);
    }

    // Accessors
    public function getIsPublishedAttribute(): bool
    {
        return $this->status === 'published';
    }

    public function getEnrollmentCountAttribute(): int
    {
        return $this->enrollments()->count();
    }

    public function getCompletionRateAttribute(): float
    {
        $total = $this->enrollments()->count();
        if ($total === 0) {
            return 0;
        }

        $completed = $this->enrollments()->where('status', 'completed')->count();
        return ($completed / $total) * 100;
    }
}
