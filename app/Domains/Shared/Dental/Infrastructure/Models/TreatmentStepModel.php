<?php

declare(strict_types=1);

namespace Modules\Dental\Infrastructure\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class TreatmentStepModel extends Model
{
    use HasFactory;

    protected $table = 'dental_treatment_steps';

    protected $fillable = [
        'treatment_plan_id',
        'name',
        'description',
        'tooth_number',
        'status',
        'cost',
        'sort_order',
        'scheduled_date',
        'completed_date',
        'performed_by',
        'metadata',
    ];

    protected $casts = [
        'cost' => 'decimal:2',
        'sort_order' => 'integer',
        'scheduled_date' => 'date',
        'completed_date' => 'date',
        'metadata' => 'array',
    ];

    public function treatmentPlan(): BelongsTo
    {
        return $this->belongsTo(TreatmentPlanModel::class, 'treatment_plan_id');
    }

    public function performedBy(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'performed_by');
    }

    public function scopeForTreatmentPlan($query, int $treatmentPlanId)
    {
        return $query->where('treatment_plan_id', $treatmentPlanId);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order');
    }
}
