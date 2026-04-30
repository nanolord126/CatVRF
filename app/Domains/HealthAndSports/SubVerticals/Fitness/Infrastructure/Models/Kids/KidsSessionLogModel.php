<?php

declare(strict_types=1);

namespace Modules\Fitness\Infrastructure\Models\Kids;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Fitness\Domain\Kids\Entities\KidsSessionLog;

final class KidsSessionLogModel extends Model
{
    use SoftDeletes;

    protected $table = 'fitness_kids_session_logs';

    protected $fillable = [
        'tenant_id',
        'enrollment_id',
        'session_date',
        'activity_type',
        'duration_minutes',
        'mood',
        'notes',
        'vitals',
    ];

    protected $casts = [
        'session_date' => 'date',
        'vitals' => 'array',
    ];

    public function enrollment()
    {
        return $this->belongsTo(KidsProgramEnrollmentModel::class, 'enrollment_id');
    }

    public static function fromDomain(KidsSessionLog $log): self
    {
        return new self([
            'id' => $log->id > 0 ? $log->id : null,
            'tenant_id' => $log->tenantId,
            'enrollment_id' => $log->enrollmentId,
            'session_date' => $log->sessionDate->toDateString(),
            'activity_type' => $log->activityType,
            'duration_minutes' => $log->durationMinutes,
            'mood' => $log->mood,
            'notes' => $log->notes,
            'vitals' => $log->vitals,
        ]);
    }

    public function updateFromDomain(KidsSessionLog $log): void
    {
        $this->session_date = $log->sessionDate->toDateString();
        $this->activity_type = $log->activityType;
        $this->duration_minutes = $log->durationMinutes;
        $this->mood = $log->mood;
        $this->notes = $log->notes;
        $this->vitals = $log->vitals;
    }

    public function toDomain(): KidsSessionLog
    {
        return new KidsSessionLog(
            id: $this->id,
            tenantId: $this->tenant_id,
            enrollmentId: $this->enrollment_id,
            sessionDate: \Carbon\CarbonImmutable::parse($this->session_date),
            activityType: $this->activity_type,
            durationMinutes: $this->duration_minutes,
            mood: $this->mood,
            notes: $this->notes,
            vitals: $this->vitals,
            createdAt: \Carbon\CarbonImmutable::parse($this->created_at),
            updatedAt: \Carbon\CarbonImmutable::parse($this->updated_at),
        );
    }
}
