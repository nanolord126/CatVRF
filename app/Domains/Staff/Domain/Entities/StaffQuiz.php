<?php

declare(strict_types=1);

namespace App\Domains\Staff\Domain\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * StaffQuiz — тест/квиз для проверки знаний.
 * CatVRF 2026 — PRODUCTION MANDATORY.
 */
final class StaffQuiz extends Model
{
    use SoftDeletes;

    protected $table = 'staff_quizzes';

    protected $fillable = [
        'uuid',
        'tenant_id',
        'course_id',
        'title',
        'description',
        'duration_minutes',
        'passing_score',
        'questions',
        'is_published',
    ];

    protected $casts = [
        'course_id' => 'integer',
        'duration_minutes' => 'integer',
        'passing_score' => 'integer',
        'questions' => 'json',
        'is_published' => 'boolean',
    ];

    protected $hidden = [
        'deleted_at',
    ];

    public function course(): BelongsTo
    {
        return $this->belongsTo(StaffCourse::class);
    }

    public function results(): HasMany
    {
        return $this->hasMany(StaffQuizResult::class);
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Tenant::class);
    }

    // Scopes
    public function scopePublished($query)
    {
        return $query->where('is_published', true);
    }

    public function scopeDraft($query)
    {
        return $query->where('is_published', false);
    }

    // Accessors
    public function getIsPublishedAttribute(): bool
    {
        return $this->is_published;
    }

    public function getQuestionCountAttribute(): int
    {
        return count($this->questions ?? []);
    }

    public function getAverageScoreAttribute(): float
    {
        $results = $this->results;
        if ($results->isEmpty()) {
            return 0;
        }

        return $results->avg('score');
    }

    public function getPassRateAttribute(): float
    {
        $results = $this->results;
        if ($results->isEmpty()) {
            return 0;
        }

        $passed = $results->where('passed', true)->count();
        return ($passed / $results->count()) * 100;
    }
}
