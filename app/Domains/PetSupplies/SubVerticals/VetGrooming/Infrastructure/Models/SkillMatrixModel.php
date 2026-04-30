<?php

declare(strict_types=1);

namespace Modules\VetGrooming\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\VetGrooming\Domain\Entities\SkillMatrix;
use Carbon\CarbonImmutable;

class SkillMatrixModel extends Model
{
    use SoftDeletes;

    protected $table = 'skill_matrices';

    protected $fillable = [
        'uuid',
        'tenant_id',
        'master_id',
        'skill',
        'skill_category',
        'proficiency_level',
        'assessed_date',
        'assessed_by',
        'assessment_notes',
        'evidence',
        'practice_hours',
        'training_completion_id',
        'correlation_id',
        'tags',
    ];

    protected $casts = [
        'assessed_date' => 'date',
        'practice_hours' => 'integer',
        'evidence' => 'array',
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

    public function assessedBy(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'assessed_by');
    }

    public function trainingCompletion(): BelongsTo
    {
        return $this->belongsTo(TrainingCompletionModel::class, 'training_completion_id');
    }

    public function toDomain(): SkillMatrix
    {
        return new SkillMatrix(
            id: $this->id,
            uuid: $this->uuid,
            tenantId: $this->tenant_id,
            masterId: $this->master_id,
            skill: $this->skill,
            skillCategory: $this->skill_category,
            proficiencyLevel: $this->proficiency_level,
            assessedDate: $this->assessed_date ? CarbonImmutable::parse($this->assessed_date) : null,
            assessedBy: $this->assessed_by,
            assessmentNotes: $this->assessment_notes,
            evidence: $this->evidence,
            practiceHours: $this->practice_hours,
            trainingCompletionId: $this->training_completion_id,
            correlationId: $this->correlation_id,
            tags: $this->tags,
            createdAt: CarbonImmutable::parse($this->created_at),
            updatedAt: CarbonImmutable::parse($this->updated_at),
            deletedAt: $this->deleted_at ? CarbonImmutable::parse($this->deleted_at) : null,
        );
    }

    public static function fromDomain(SkillMatrix $entity): self
    {
        $model = new self();
        $model->id = $entity->id;
        $model->uuid = $entity->uuid;
        $model->tenant_id = $entity->tenantId;
        $model->master_id = $entity->masterId;
        $model->skill = $entity->skill;
        $model->skill_category = $entity->skillCategory;
        $model->proficiency_level = $entity->proficiencyLevel;
        $model->assessed_date = $entity->assessedDate?->toDateString();
        $model->assessed_by = $entity->assessedBy;
        $model->assessment_notes = $entity->assessmentNotes;
        $model->evidence = $entity->evidence;
        $model->practice_hours = $entity->practiceHours;
        $model->training_completion_id = $entity->trainingCompletionId;
        $model->correlation_id = $entity->correlationId;
        $model->tags = $entity->tags;

        return $model;
    }
}
