<?php

declare(strict_types=1);

namespace Modules\Dental\Infrastructure\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

final class TreatmentPlanModel extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'dental_treatment_plans';

    protected $fillable = [
        'patient_id',
        'doctor_id',
        'tooth_chart_id',
        'tenant_id',
        'name',
        'description',
        'status',
        'total_cost',
        'discount_amount',
        'final_cost',
        'start_date',
        'estimated_completion_date',
        'actual_completion_date',
        'metadata',
    ];

    protected $casts = [
        'total_cost' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'final_cost' => 'decimal:2',
        'start_date' => 'date',
        'estimated_completion_date' => 'date',
        'actual_completion_date' => 'date',
        'metadata' => 'array',
    ];

    public function patient(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'patient_id');
    }

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'doctor_id');
    }

    public function toothChart(): BelongsTo
    {
        return $this->belongsTo(ToothChartModel::class, 'tooth_chart_id');
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Tenant::class, 'tenant_id');
    }

    public function steps(): HasMany
    {
        return $this->hasMany(TreatmentStepModel::class, 'treatment_plan_id');
    }

    public function scopeForPatient($query, int $patientId)
    {
        return $query->where('patient_id', $patientId);
    }

    public function scopeForTenant($query, int $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    public function scopeWithStatus($query, string $status)
    {
        return $query->where('status', $status);
    }
}
