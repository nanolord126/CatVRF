<?php

declare(strict_types=1);

namespace App\Domains\Staff\Domain\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * StaffReview — peer review (отзыв между сотрудниками).
 * CatVRF 2026 — PRODUCTION MANDATORY.
 */
final class StaffReview extends Model
{
    use SoftDeletes;

    protected $table = 'staff_reviews';

    protected $fillable = [
        'uuid',
        'tenant_id',
        'reviewer_id',
        'reviewee_id',
        'overall_rating',
        'communication_rating',
        'teamwork_rating',
        'leadership_rating',
        'technical_rating',
        'strengths',
        'areas_for_improvement',
        'comments',
        'status',
        'is_anonymous',
        'period',
    ];

    protected $casts = [
        'overall_rating' => 'decimal:2',
        'communication_rating' => 'decimal:2',
        'teamwork_rating' => 'decimal:2',
        'leadership_rating' => 'decimal:2',
        'technical_rating' => 'decimal:2',
        'is_anonymous' => 'boolean',
    ];

    protected $hidden = [
        'deleted_at',
    ];

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'reviewer_id');
    }

    public function reviewee(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'reviewee_id');
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Tenant::class);
    }

    // Scopes
    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeByPeriod($query, string $period)
    {
        return $query->where('period', $period);
    }

    public function scopeAnonymous($query)
    {
        return $query->where('is_anonymous', true);
    }

    // Accessors
    public function getAverageRatingAttribute(): float
    {
        $ratings = array_filter([
            $this->communication_rating,
            $this->teamwork_rating,
            $this->leadership_rating,
            $this->technical_rating,
        ]);

        return count($ratings) > 0 
            ? array_sum($ratings) / count($ratings) 
            : 0;
    }
}
