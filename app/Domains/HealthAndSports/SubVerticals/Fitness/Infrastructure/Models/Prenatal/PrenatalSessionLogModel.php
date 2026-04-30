<?php

declare(strict_types=1);

namespace Modules\Fitness\Infrastructure\Models\Prenatal;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Fitness\Domain\Prenatal\Entities\PrenatalSessionLog;

final class PrenatalSessionLogModel extends Model
{
    use SoftDeletes;

    protected $table = 'fitness_prenatal_session_logs';

    protected $fillable = [
        'tenant_id',
        'enrollment_id',
        'session_date',
        'exercise_type',
        'duration_minutes',
        'heart_rate_before',
        'heart_rate_after',
        'blood_pressure_systolic',
        'blood_pressure_diastolic',
        'notes',
        'vitals',
    ];

    protected $casts = [
        'session_date' => 'date',
        'vitals' => 'array',
    ];

    public function enrollment()
    {
        return $this->belongsTo(PrenatalEnrollmentModel::class, 'enrollment_id');
    }

    public static function fromDomain(PrenatalSessionLog $log): self
    {
        return new self([
            'id' => $log->id > 0 ? $log->id : null,
            'tenant_id' => $log->tenantId,
            'enrollment_id' => $log->enrollmentId,
            'session_date' => $log->sessionDate->toDateString(),
            'exercise_type' => $log->exerciseType,
            'duration_minutes' => $log->durationMinutes,
            'heart_rate_before' => $log->heartRateBefore,
            'heart_rate_after' => $log->heartRateAfter,
            'blood_pressure_systolic' => $log->bloodPressureSystolic,
            'blood_pressure_diastolic' => $log->bloodPressureDiastolic,
            'notes' => $log->notes,
            'vitals' => $log->vitals,
        ]);
    }

    public function updateFromDomain(PrenatalSessionLog $log): void
    {
        $this->session_date = $log->sessionDate->toDateString();
        $this->exercise_type = $log->exerciseType;
        $this->duration_minutes = $log->durationMinutes;
        $this->heart_rate_before = $log->heartRateBefore;
        $this->heart_rate_after = $log->heartRateAfter;
        $this->blood_pressure_systolic = $log->bloodPressureSystolic;
        $this->blood_pressure_diastolic = $log->bloodPressureDiastolic;
        $this->notes = $log->notes;
        $this->vitals = $log->vitals;
    }

    public function toDomain(): PrenatalSessionLog
    {
        return new PrenatalSessionLog(
            id: $this->id,
            tenantId: $this->tenant_id,
            enrollmentId: $this->enrollment_id,
            sessionDate: \Carbon\CarbonImmutable::parse($this->session_date),
            exerciseType: $this->exercise_type,
            durationMinutes: $this->duration_minutes,
            heartRateBefore: $this->heart_rate_before,
            heartRateAfter: $this->heart_rate_after,
            bloodPressureSystolic: $this->blood_pressure_systolic,
            bloodPressureDiastolic: $this->blood_pressure_diastolic,
            notes: $this->notes,
            vitals: $this->vitals,
            createdAt: \Carbon\CarbonImmutable::parse($this->created_at),
            updatedAt: \Carbon\CarbonImmutable::parse($this->updated_at),
        );
    }
}
