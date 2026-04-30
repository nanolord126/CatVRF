<?php

declare(strict_types=1);

namespace Modules\Fitness\Infrastructure\Models\Kids;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Fitness\Domain\Kids\Entities\KidsProgramEnrollment;

final class KidsProgramEnrollmentModel extends Model
{
    use SoftDeletes;

    protected $table = 'fitness_kids_program_enrollments';

    protected $fillable = [
        'tenant_id',
        'client_id',
        'kids_program_id',
        'start_date',
        'progress_percent',
        'status',
        'medical_clearance_status',
        'medical_clearance_date',
        'initial_assessment',
        'final_assessment',
        'notes',
    ];

    protected $casts = [
        'progress_percent' => 'decimal:2',
        'start_date' => 'date',
        'medical_clearance_date' => 'date',
        'initial_assessment' => 'array',
        'final_assessment' => 'array',
    ];

    public function kidsProgram()
    {
        return $this->belongsTo(KidsProgramModel::class, 'kids_program_id');
    }

    public static function fromDomain(KidsProgramEnrollment $enrollment): self
    {
        return new self([
            'id' => $enrollment->id > 0 ? $enrollment->id : null,
            'tenant_id' => $enrollment->tenantId,
            'client_id' => $enrollment->clientId,
            'kids_program_id' => $enrollment->kidsProgramId,
            'start_date' => $enrollment->startDate->toDateString(),
            'progress_percent' => $enrollment->progressPercent,
            'status' => $enrollment->status,
            'medical_clearance_status' => $enrollment->medicalClearanceStatus,
            'medical_clearance_date' => $enrollment->medicalClearanceDate?->toDateString(),
            'initial_assessment' => $enrollment->initialAssessment,
            'final_assessment' => $enrollment->finalAssessment,
            'notes' => $enrollment->notes,
        ]);
    }

    public function updateFromDomain(KidsProgramEnrollment $enrollment): void
    {
        $this->start_date = $enrollment->startDate->toDateString();
        $this->progress_percent = $enrollment->progressPercent;
        $this->status = $enrollment->status;
        $this->medical_clearance_status = $enrollment->medicalClearanceStatus;
        $this->medical_clearance_date = $enrollment->medicalClearanceDate?->toDateString();
        $this->initial_assessment = $enrollment->initialAssessment;
        $this->final_assessment = $enrollment->finalAssessment;
        $this->notes = $enrollment->notes;
    }

    public function toDomain(): KidsProgramEnrollment
    {
        return new KidsProgramEnrollment(
            id: $this->id,
            tenantId: $this->tenant_id,
            clientId: $this->client_id,
            kidsProgramId: $this->kids_program_id,
            startDate: \Carbon\CarbonImmutable::parse($this->start_date),
            progressPercent: (float) $this->progress_percent,
            status: $this->status,
            medicalClearanceStatus: $this->medical_clearance_status,
            medicalClearanceDate: $this->medical_clearance_date ? \Carbon\CarbonImmutable::parse($this->medical_clearance_date) : null,
            initialAssessment: $this->initial_assessment,
            finalAssessment: $this->final_assessment,
            notes: $this->notes,
            createdAt: \Carbon\CarbonImmutable::parse($this->created_at),
            updatedAt: \Carbon\CarbonImmutable::parse($this->updated_at),
        );
    }
}
