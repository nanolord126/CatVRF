<?php

declare(strict_types=1);

namespace Modules\VetGrooming\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\VetGrooming\Domain\Entities\TrainingCompletion;
use Carbon\CarbonImmutable;

class TrainingCompletionModel extends Model
{
    use SoftDeletes;

    protected $table = 'training_completions';

    protected $fillable = [
        'uuid',
        'tenant_id',
        'master_id',
        'course_id',
        'development_plan_id',
        'completion_date',
        'duration_actual_hours',
        'score',
        'grade',
        'certificate_file',
        'certificate_expiry_date',
        'verification_status',
        'verified_by',
        'verified_at',
        'evidence_files',
        'feedback',
        'external_certification_id',
        'external_platform',
        'correlation_id',
        'tags',
    ];

    protected $casts = [
        'completion_date' => 'date',
        'duration_actual_hours' => 'integer',
        'score' => 'decimal:2',
        'certificate_expiry_date' => 'date',
        'verified_at' => 'datetime',
        'evidence_files' => 'array',
        'tags' => 'array',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Tenant::class, 'tenant_id');
    }

    public function master(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Veterinarian::class, 'master_id');
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(TrainingCourseModel::class, 'course_id');
    }

    public function developmentPlan(): BelongsTo
    {
        return $this->belongsTo(ProfessionalDevelopmentPlanModel::class, 'development_plan_id');
    }

    public function verifiedBy(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'verified_by');
    }

    public function toDomain(): TrainingCompletion
    {
        return new TrainingCompletion(
            id: $this->id,
            uuid: $this->uuid,
            tenantId: $this->tenant_id,
            masterId: $this->master_id,
            courseId: $this->course_id,
            developmentPlanId: $this->development_plan_id,
            completionDate: CarbonImmutable::parse($this->completion_date),
            durationActualHours: $this->duration_actual_hours,
            score: $this->score !== null ? (float) $this->score : null,
            grade: $this->grade,
            certificateFile: $this->certificate_file,
            certificateExpiryDate: $this->certificate_expiry_date ? CarbonImmutable::parse($this->certificate_expiry_date) : null,
            verificationStatus: $this->verification_status,
            verifiedBy: $this->verified_by,
            verifiedAt: $this->verified_at ? CarbonImmutable::parse($this->verified_at) : null,
            evidenceFiles: $this->evidence_files,
            feedback: $this->feedback,
            externalCertificationId: $this->external_certification_id,
            externalPlatform: $this->external_platform,
            correlationId: $this->correlation_id,
            tags: $this->tags,
            createdAt: CarbonImmutable::parse($this->created_at),
            updatedAt: CarbonImmutable::parse($this->updated_at),
            deletedAt: $this->deleted_at ? CarbonImmutable::parse($this->deleted_at) : null,
        );
    }

    public static function fromDomain(TrainingCompletion $entity): self
    {
        $model = new self();
        $model->id = $entity->id;
        $model->uuid = $entity->uuid;
        $model->tenant_id = $entity->tenantId;
        $model->master_id = $entity->masterId;
        $model->course_id = $entity->courseId;
        $model->development_plan_id = $entity->developmentPlanId;
        $model->completion_date = $entity->completionDate->toDateString();
        $model->duration_actual_hours = $entity->durationActualHours;
        $model->score = $entity->score;
        $model->grade = $entity->grade;
        $model->certificate_file = $entity->certificateFile;
        $model->certificate_expiry_date = $entity->certificateExpiryDate?->toDateString();
        $model->verification_status = $entity->verificationStatus;
        $model->verified_by = $entity->verifiedBy;
        $model->verified_at = $entity->verifiedAt?->toDateTimeString();
        $model->evidence_files = $entity->evidenceFiles;
        $model->feedback = $entity->feedback;
        $model->external_certification_id = $entity->externalCertificationId;
        $model->external_platform = $entity->externalPlatform;
        $model->correlation_id = $entity->correlationId;
        $model->tags = $entity->tags;

        return $model;
    }
}
