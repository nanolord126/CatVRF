<?php

declare(strict_types=1);

namespace App\Domains\Staff\Domain\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * StaffQuizResult — результат прохождения теста.
 * CatVRF 2026 — PRODUCTION MANDATORY.
 */
final class StaffQuizResult extends Model
{
    protected $table = 'staff_quiz_results';

    protected $fillable = [
        'uuid',
        'tenant_id',
        'staff_id',
        'quiz_id',
        'score',
        'correct_answers',
        'total_questions',
        'passed',
        'answers',
        'time_spent_seconds',
        'completed_at',
    ];

    protected $casts = [
        'score' => 'decimal:2',
        'correct_answers' => 'integer',
        'total_questions' => 'integer',
        'passed' => 'boolean',
        'answers' => 'json',
        'time_spent_seconds' => 'integer',
        'completed_at' => 'datetime',
    ];

    public function staff(): BelongsTo
    {
        return $this->belongsTo(Staff::class);
    }

    public function quiz(): BelongsTo
    {
        return $this->belongsTo(StaffQuiz::class);
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Tenant::class);
    }

    // Scopes
    public function scopePassed($query)
    {
        return $query->where('passed', true);
    }

    public function scopeFailed($query)
    {
        return $query->where('passed', false);
    }

    public function scopeRecent($query, int $days = 30)
    {
        return $query->where('completed_at', '>=', now()->subDays($days));
    }

    // Accessors
    public function getPercentageAttribute(): float
    {
        return $this->total_questions > 0 
            ? ($this->correct_answers / $this->total_questions) * 100 
            : 0;
    }

    public function getTimeSpentMinutesAttribute(): float
    {
        return $this->time_spent_seconds / 60;
    }

    public function getIsPassingScoreAttribute(): bool
    {
        return $this->score >= $this->quiz->passing_score;
    }
}
