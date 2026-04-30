<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\VerificationResult;
use App\Enums\VerificationType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

final class VerificationLog extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'tenant_id',
        'business_group_id',
        'type',
        'provider',
        'score',
        'result',
        'metadata',
        'reason',
        'correlation_id',
    ];

    protected $casts = [
        'score' => 'float',
        'metadata' => 'json',
    ];

    protected $table = 'verification_logs';

    // ========================
    // RELATIONSHIPS
    // ========================

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function businessGroup(): BelongsTo
    {
        return $this->belongsTo(BusinessGroup::class);
    }

    // ========================
    // SCOPES
    // ========================

    public function scopeByUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeByTenant($query, int $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    public function scopeByBusinessGroup($query, int $businessGroupId)
    {
        return $query->where('business_group_id', $businessGroupId);
    }

    public function scopeByType($query, VerificationType $type)
    {
        return $query->where('type', $type->value);
    }

    public function scopeByProvider($query, string $provider)
    {
        return $query->where('provider', $provider);
    }

    public function scopeSuccessful($query)
    {
        return $query->where('result', VerificationResult::Success->value);
    }

    public function scopeFailed($query)
    {
        return $query->where('result', VerificationResult::Failed->value);
    }

    public function scopePending($query)
    {
        return $query->whereIn('result', [
            VerificationResult::Pending->value,
            VerificationResult::RequiresReview->value,
        ]);
    }

    // ========================
    // HELPER METHODS
    // ========================

    public function isSuccess(): bool
    {
        return $this->result === VerificationResult::Success->value;
    }

    public function isFailed(): bool
    {
        return $this->result === VerificationResult::Failed->value;
    }

    public function isPending(): bool
    {
        return in_array($this->result, [
            VerificationResult::Pending->value,
            VerificationResult::RequiresReview->value,
        ], true);
    }

    public function getType(): VerificationType
    {
        return VerificationType::from($this->type);
    }

    public function getResult(): VerificationResult
    {
        return VerificationResult::from($this->result);
    }
}
