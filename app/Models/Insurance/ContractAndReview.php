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

    protected static function booted(): void
    {
        static::creating(function (self $model) {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid();
            }
        });

        static::addGlobalScope('tenant', function ($builder) {
            if (auth()->check()) {
                $builder->where('tenant_id', auth()->user()->tenant_id);
            }
        });
    }

    /**
     * The company being reviewed.
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(InsuranceCompany::class, 'company_id');
    }

    /**
     * The user that submitted the review.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}

/**
 * InsuranceContract Model.
 * Implementation: 9-LAYER ARCHITECTURE 2026.
 * Metadata: Digital Signature, Document Tracking.
 */
final class InsuranceContract extends Model
{
    protected $table = 'insurance_contracts';

    protected $fillable = [
        'uuid',
        'tenant_id',
        'policy_id',
        'document_url',
        'signed_at',
        'digital_signature',
        'correlation_id',
    ];

    protected $casts = [
        'digital_signature' => 'json',
        'signed_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $model) {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid();
            }
        });

        static::addGlobalScope('tenant', function ($builder) {
            if (auth()->check()) {
                $builder->where('tenant_id', auth()->user()->tenant_id);
            }
        });
    }

    /**
     * The policy related to this legal contract.
     */
    public function policy(): BelongsTo
    {
        return $this->belongsTo(InsurancePolicy::class, 'policy_id');
    }

    /**
     * Mark the contract as signed with a digital footprint.
     */
    public function sign(array $signature): bool
    {
        $this->update([
            'signed_at' => now(),
            'digital_signature' => $signature,
        ]);

        return true;
    }
}
