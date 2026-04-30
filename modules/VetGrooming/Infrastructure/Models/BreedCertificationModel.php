<?php

declare(strict_types=1);

namespace Modules\VetGrooming\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\VetGrooming\Domain\Entities\BreedCertification;
use Carbon\CarbonImmutable;

class BreedCertificationModel extends Model
{
    use SoftDeletes;

    protected $table = 'breed_certifications';

    protected $fillable = [
        'uuid',
        'tenant_id',
        'master_id',
        'certification_type',
        'breed_group',
        'breed_id',
        'certification_level',
        'issuer',
        'external_school_name',
        'issue_date',
        'expiry_date',
        'practical_exam_score',
        'theory_exam_score',
        'exam_feedback',
        'status',
        'certificate_file',
        'certificate_number',
        'verified_by',
        'verified_at',
        'notes',
        'special_skills',
        'correlation_id',
        'tags',
    ];

    protected $casts = [
        'issue_date' => 'date',
        'expiry_date' => 'date',
        'practical_exam_score' => 'decimal:2',
        'theory_exam_score' => 'decimal:2',
        'verified_at' => 'datetime',
        'special_skills' => 'array',
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

    public function verifiedBy(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'verified_by');
    }

    public function toDomain(): BreedCertification
    {
        return new BreedCertification(
            id: $this->id,
            uuid: $this->uuid,
            tenantId: $this->tenant_id,
            masterId: $this->master_id,
            certificationType: $this->certification_type,
            breedGroup: $this->breed_group,
            breedId: $this->breed_id,
            certificationLevel: $this->certification_level,
            issuer: $this->issuer,
            externalSchoolName: $this->external_school_name,
            issueDate: CarbonImmutable::parse($this->issue_date),
            expiryDate: $this->expiry_date ? CarbonImmutable::parse($this->expiry_date) : null,
            practicalExamScore: $this->practical_exam_score !== null ? (float) $this->practical_exam_score : null,
            theoryExamScore: $this->theory_exam_score !== null ? (float) $this->theory_exam_score : null,
            examFeedback: $this->exam_feedback,
            status: $this->status,
            certificateFile: $this->certificate_file,
            certificateNumber: $this->certificate_number,
            verifiedBy: $this->verified_by,
            verifiedAt: $this->verified_at ? CarbonImmutable::parse($this->verified_at) : null,
            notes: $this->notes,
            specialSkills: $this->special_skills,
            correlationId: $this->correlation_id,
            tags: $this->tags,
            createdAt: CarbonImmutable::parse($this->created_at),
            updatedAt: CarbonImmutable::parse($this->updated_at),
            deletedAt: $this->deleted_at ? CarbonImmutable::parse($this->deleted_at) : null,
        );
    }

    public static function fromDomain(BreedCertification $entity): self
    {
        $model = new self();
        $model->id = $entity->id;
        $model->uuid = $entity->uuid;
        $model->tenant_id = $entity->tenantId;
        $model->master_id = $entity->masterId;
        $model->certification_type = $entity->certificationType;
        $model->breed_group = $entity->breedGroup;
        $model->breed_id = $entity->breedId;
        $model->certification_level = $entity->certificationLevel;
        $model->issuer = $entity->issuer;
        $model->external_school_name = $entity->externalSchoolName;
        $model->issue_date = $entity->issueDate->toDateString();
        $model->expiry_date = $entity->expiryDate?->toDateString();
        $model->practical_exam_score = $entity->practicalExamScore;
        $model->theory_exam_score = $entity->theoryExamScore;
        $model->exam_feedback = $entity->examFeedback;
        $model->status = $entity->status;
        $model->certificate_file = $entity->certificateFile;
        $model->certificate_number = $entity->certificateNumber;
        $model->verified_by = $entity->verifiedBy;
        $model->verified_at = $entity->verifiedAt?->toDateTimeString();
        $model->notes = $entity->notes;
        $model->special_skills = $entity->specialSkills;
        $model->correlation_id = $entity->correlationId;
        $model->tags = $entity->tags;

        return $model;
    }
}
