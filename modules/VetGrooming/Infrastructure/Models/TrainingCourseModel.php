<?php

declare(strict_types=1);

namespace Modules\VetGrooming\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\VetGrooming\Domain\Entities\TrainingCourse;
use Carbon\CarbonImmutable;

class TrainingCourseModel extends Model
{
    use SoftDeletes;

    protected $table = 'training_courses';

    protected $fillable = [
        'uuid',
        'tenant_id',
        'title',
        'description',
        'category',
        'profession_type',
        'specialization',
        'duration_hours',
        'required_for_level',
        'is_mandatory',
        'mandatory_for_specializations',
        'mandatory_for_breeds',
        'certificate_issued',
        'certificate_template',
        'learning_objectives',
        'prerequisites',
        'is_active',
        'correlation_id',
        'tags',
    ];

    protected $casts = [
        'duration_hours' => 'integer',
        'is_mandatory' => 'boolean',
        'certificate_issued' => 'boolean',
        'is_active' => 'boolean',
        'mandatory_for_specializations' => 'array',
        'mandatory_for_breeds' => 'array',
        'learning_objectives' => 'array',
        'prerequisites' => 'array',
        'tags' => 'array',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Tenant::class, 'tenant_id');
    }

    public function toDomain(): TrainingCourse
    {
        return new TrainingCourse(
            id: $this->id,
            uuid: $this->uuid,
            tenantId: $this->tenant_id,
            title: $this->title,
            description: $this->description,
            category: $this->category,
            professionType: $this->profession_type,
            specialization: $this->specialization,
            durationHours: $this->duration_hours,
            requiredForLevel: $this->required_for_level,
            isMandatory: $this->is_mandatory,
            mandatoryForSpecializations: $this->mandatory_for_specializations,
            mandatoryForBreeds: $this->mandatory_for_breeds,
            certificateIssued: $this->certificate_issued,
            certificateTemplate: $this->certificate_template,
            learningObjectives: $this->learning_objectives,
            prerequisites: $this->prerequisites,
            isActive: $this->is_active,
            correlationId: $this->correlation_id,
            tags: $this->tags,
            createdAt: CarbonImmutable::parse($this->created_at),
            updatedAt: CarbonImmutable::parse($this->updated_at),
            deletedAt: $this->deleted_at ? CarbonImmutable::parse($this->deleted_at) : null,
        );
    }

    public static function fromDomain(TrainingCourse $entity): self
    {
        $model = new self();
        $model->id = $entity->id;
        $model->uuid = $entity->uuid;
        $model->tenant_id = $entity->tenantId;
        $model->title = $entity->title;
        $model->description = $entity->description;
        $model->category = $entity->category;
        $model->profession_type = $entity->professionType;
        $model->specialization = $entity->specialization;
        $model->duration_hours = $entity->durationHours;
        $model->required_for_level = $entity->requiredForLevel;
        $model->is_mandatory = $entity->isMandatory;
        $model->mandatory_for_specializations = $entity->mandatoryForSpecializations;
        $model->mandatory_for_breeds = $entity->mandatoryForBreeds;
        $model->certificate_issued = $entity->certificateIssued;
        $model->certificate_template = $entity->certificateTemplate;
        $model->learning_objectives = $entity->learningObjectives;
        $model->prerequisites = $entity->prerequisites;
        $model->is_active = $entity->isActive;
        $model->correlation_id = $entity->correlationId;
        $model->tags = $entity->tags;

        return $model;
    }
}
