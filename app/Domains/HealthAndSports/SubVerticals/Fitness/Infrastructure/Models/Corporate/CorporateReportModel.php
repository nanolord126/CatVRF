<?php

declare(strict_types=1);

namespace Modules\Fitness\Infrastructure\Models\Corporate;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Fitness\Domain\Corporate\Entities\CorporateReport;

final class CorporateReportModel extends Model
{
    use SoftDeletes;

    protected $table = 'fitness_corporate_reports';

    protected $fillable = [
        'tenant_id',
        'corporate_enrollment_id',
        'period_start',
        'period_end',
        'attendance_rate',
        'active_employees',
        'total_workouts',
        'top_workouts',
        'employee_stats',
        'notes',
    ];

    protected $casts = [
        'period_start' => 'date',
        'period_end' => 'date',
        'attendance_rate' => 'decimal:2',
        'top_workouts' => 'array',
        'employee_stats' => 'array',
    ];

    public function corporateEnrollment()
    {
        return $this->belongsTo(CorporateEnrollmentModel::class, 'corporate_enrollment_id');
    }

    public static function fromDomain(CorporateReport $report): self
    {
        return new self([
            'id' => $report->id > 0 ? $report->id : null,
            'tenant_id' => $report->tenantId,
            'corporate_enrollment_id' => $report->corporateEnrollmentId,
            'period_start' => $report->periodStart->toDateString(),
            'period_end' => $report->periodEnd->toDateString(),
            'attendance_rate' => $report->attendanceRate,
            'active_employees' => $report->activeEmployees,
            'total_workouts' => $report->totalWorkouts,
            'top_workouts' => $report->topWorkouts,
            'employee_stats' => $report->employeeStats,
            'notes' => $report->notes,
        ]);
    }

    public function updateFromDomain(CorporateReport $report): void
    {
        $this->period_start = $report->periodStart->toDateString();
        $this->period_end = $report->periodEnd->toDateString();
        $this->attendance_rate = $report->attendanceRate;
        $this->active_employees = $report->activeEmployees;
        $this->total_workouts = $report->totalWorkouts;
        $this->top_workouts = $report->topWorkouts;
        $this->employee_stats = $report->employeeStats;
        $this->notes = $report->notes;
    }

    public function toDomain(): CorporateReport
    {
        return new CorporateReport(
            id: $this->id,
            tenantId: $this->tenant_id,
            corporateEnrollmentId: $this->corporate_enrollment_id,
            periodStart: \Carbon\CarbonImmutable::parse($this->period_start),
            periodEnd: \Carbon\CarbonImmutable::parse($this->period_end),
            attendanceRate: (float) $this->attendance_rate,
            activeEmployees: $this->active_employees,
            totalWorkouts: $this->total_workouts,
            topWorkouts: $this->top_workouts,
            employeeStats: $this->employee_stats,
            notes: $this->notes,
            createdAt: \Carbon\CarbonImmutable::parse($this->created_at),
            updatedAt: \Carbon\CarbonImmutable::parse($this->updated_at),
        );
    }
}
