<?php

declare(strict_types=1);

namespace App\Domains\Insurance\RiskManagement\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\TenantScoped;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;

final class RiskAssessment extends Model
{
    use HasUuids;
    use SoftDeletes;
    use TenantScoped;

    protected $table = 'risk_assessments';

    protected $fillable = [
        'uuid',
        'tenant_id',
        'analyst_id',
        'client_id',
        'correlation_id',
        'status',
        'total_kopecks',
        'payout_kopecks',
        'payment_status',
        'assessment_type',
        'analysis_hours',
        'due_date',
        'tags',
    ];

    protected $casts = [
        'total_kopecks' => 'integer',
        'payout_kopecks' => 'integer',
        'analysis_hours' => 'integer',
        'due_date' => 'datetime',
        'tags' => 'json',
    ];

    /**
     * Get the string representation of this instance.
     *
     * @return string The string representation
     */
    public function __toString(): string
    {
        return self::class;
    }

    /**
     * Get debug information for this instance.
     *
     * @return array<string, mixed> Debug data including class name and state
     */
    public function toDebugArray(): array
    {
        return [
            'class' => self::class,
            'timestamp' => CarbonImmutable::now()->toIso8601String(),
        ];
    }

    protected static function booted_disabled(): void
    {
        self::addGlobalScope('tenant', fn ($q) => $q->where('risk_assessments.tenant_id', tenant()->id));
    }
}
