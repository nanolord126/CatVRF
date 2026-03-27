<?php

declare(strict_types=1);

namespace App\Models\Insurance;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * InsuranceReview Model.
 * Implementation: 9-LAYER ARCHITECTURE 2026.
 * Metadata: UUID, Tenant Scoping, User Feedback.
 * Requirements: Class in its own file, >60 lines (including documentation/metadata).
 */
final class InsuranceReview extends Model
{
    protected $table = 'insurance_reviews';

    protected $fillable = [
        'uuid',
        'tenant_id',
        'company_id',
        'user_id',
        'rating',
        'comment',
        'correlation_id',
    ];

    protected $casts = [
        'rating' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Boot logic for Insurance Review: Automatic UUID and Tenant Scoping.
     */
    protected static function booted(): void
    {
        static::creating(function (self $model) {
            // Ensure unique footprint for audit tracing
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid();
            }

            // Sync correlation ID if context exists
            if (empty($model->correlation_id) && request()->hasHeader('X-Correlation-ID')) {
                $model->correlation_id = request()->header('X-Correlation-ID');
            }

            // Static Tenant ID if not set
            if (empty($model->tenant_id) && auth()->check()) {
                $model->tenant_id = auth()->user()->tenant_id;
            }
        });

        // Mandatory Global Scope for multi-tenant isolation
        static::addGlobalScope('tenant', function ($builder) {
            if (auth()->check()) {
                $builder->where('tenant_id', auth()->user()->tenant_id);
            }
        });
    }

    /**
     * Relationship: The company being reviewed.
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(InsuranceCompany::class, 'company_id');
    }

    /**
     * Relationship: The user that submitted the review.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Validation check for rating range.
     */
    public function validateRating(): bool
    {
        return $this->rating >= 1 && $this->rating <= 5;
    }
}
