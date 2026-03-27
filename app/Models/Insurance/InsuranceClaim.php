<?php

declare(strict_types=1);

namespace App\Models\Insurance;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * InsuranceClaim Model.
 * Vertical: Insurance.
 * Implementation: 2026 Canonical Model.
 * Metadata: UUID, Fraud Scoring, JSONB, SoftDeletes.
 */
final class InsuranceClaim extends Model
{
    protected $table = 'insurance_claims';

    protected $fillable = [
        'uuid',
        'tenant_id',
        'policy_id',
        'claim_number',
        'description',
        'requested_amount',
        'approved_amount',
        'status',
        'evidence_files',
        'fraud_score',
        'correlation_id',
    ];

    protected $casts = [
        'evidence_files' => 'json',
        'fraud_score' => 'json', // Confidence score from FraudControlService
        'requested_amount' => 'integer',
        'approved_amount' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'status' => 'string',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $model) {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid();
            }
            if (empty($model->claim_number)) {
                $model->claim_number = 'CLM-' . strtoupper(Str::random(10));
            }
        });

        static::addGlobalScope('tenant', function ($builder) {
            if (auth()->check()) {
                $builder->where('tenant_id', auth()->user()->tenant_id);
            }
        });
    }

    /**
     * The policy related to this claim.
     */
    public function policy(): BelongsTo
    {
        return $this->belongsTo(InsurancePolicy::class, 'policy_id');
    }

    /**
     * The owner of the policy filing the claim.
     */
    public function user(): BelongsTo
    {
        return $this->policy->user();
    }

    /**
     * Check if the claim is already processed (approved, rejected, or paid).
     */
    public function isProcessed(): bool
    {
        return in_array($this->status, ['approved', 'rejected', 'paid'], true);
    }
}
