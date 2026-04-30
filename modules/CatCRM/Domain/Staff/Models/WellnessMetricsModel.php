<?php

declare(strict_types=1);

namespace Modules\CatCRM\Domain\Staff\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * WellnessMetricsModel — Layer 9: Database/Persistence Layer (Eloquent Model)
 */
final class WellnessMetricsModel extends Model
{
    use HasFactory;

    protected $table = 'staff_wellness_metrics';

    protected $fillable = [
        'tenant_id',
        'employee_id',
        'stress_level',
        'sleep_hours',
        'work_hours',
        'breaks_taken',
        'mood_score',
        'energy_level',
        'work_life_balance',
        'steps_count',
        'active_minutes',
        'recorded_at',
        'metadata',
    ];

    protected $casts = [
        'recorded_at' => 'datetime',
        'steps_count' => 'decimal:2',
        'active_minutes' => 'decimal:2',
        'metadata' => 'json',
    ];

    public function employee()
    {
        return $this->belongsTo(EmployeeModel::class, 'employee_id');
    }
}
