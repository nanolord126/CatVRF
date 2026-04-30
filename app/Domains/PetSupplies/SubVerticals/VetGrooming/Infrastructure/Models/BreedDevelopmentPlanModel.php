<?php

declare(strict_types=1);

namespace Modules\VetGrooming\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\VetGrooming\Domain\Entities\BreedDevelopmentPlan;
use Carbon\CarbonImmutable;

class BreedDevelopmentPlanModel extends Model
{
    use SoftDeletes;

    protected $table = 'breed_development_plans';

    protected $fillable = [
        'uuid',
        'tenant_id',
        'master_id',
        'target_breed_group',
        'target_breed',
        'target_level',
        'plan_start_date',
        'target_completion_date',
        'training_hours_completed',
        'practical_hours_completed',
        'required_training_hours',
        'required_practical_hours',
        'progress_percentage',
        'status',
        'prerequisites',
        'prerequisites_met',
        'training_modules',
        'practical_tasks',
        'mentor_id',
        'goals',
        'obstacles',
        'correlation_id',
        'tags',
    ];

    protected $casts = [
        'plan_start_date' => 'date',
        'target_completion_date' => 'date',
        'training_hours_completed' => 'integer',
        'practical_hours_completed' => 'integer',
        'required_training_hours' => 'integer',
        'required_practical_hours' => 'integer',
        'progress_percentage' => 'decimal:2',
        'prerequisites_met' => 'boolean',
        'prerequisites' => 'array',
        'training_modules' => 'array',
        'practical_tasks' => 'array',
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

    public function mentor(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Veterinarian::class, 'mentor_id');
    }

    public function toDomain(): BreedDevelopmentPlan
    {
        return new BreedDevelopmentPlan(
            id: $this->id,
            uuid: $this->uuid,
            tenantId: $this->tenant_id,
            masterId: $this->master_id,
            targetBreedGroup: $this->target_breed_group,
            targetBreed: $this->target_breed,
            targetLevel: $this->target_level,
            planStartDate: CarbonImmutable::parse($this->plan_start_date),
            targetCompletionDate: CarbonImmutable::parse($this->target_completion_date),
            trainingHoursCompleted: $this->training_hours_completed,
            practicalHoursCompleted: $this->practical_hours_completed,
            requiredTrainingHours: $this->required_training_hours,
            requiredPracticalHours: $this->required_practical_hours,
            progressPercentage: (float) $this->progress_percentage,
            status: $this->status,
            prerequisites: $this->prerequisites,
            prerequisitesMet: $this->prerequisites_met,
            trainingModules: $this->training_modules,
            practicalTasks: $this->practical_tasks,
            mentorId: $this->mentor_id,
            goals: $this->goals,
            obstacles: $this->obstacles,
            correlationId: $this->correlation_id,
            tags: $this->tags,
            createdAt: CarbonImmutable::parse($this->created_at),
            updatedAt: CarbonImmutable::parse($this->updated_at),
            deletedAt: $this->deleted_at ? CarbonImmutable::parse($this->deleted_at) : null,
        );
    }

    public static function fromDomain(BreedDevelopmentPlan $entity): self
    {
        $model = new self();
        $model->id = $entity->id;
        $model->uuid = $entity->uuid;
        $model->tenant_id = $entity->tenantId;
        $model->master_id = $entity->masterId;
        $model->target_breed_group = $entity->targetBreedGroup;
        $model->target_breed = $entity->targetBreed;
        $model->target_level = $entity->targetLevel;
        $model->plan_start_date = $entity->planStartDate->toDateString();
        $model->target_completion_date = $entity->targetCompletionDate->toDateString();
        $model->training_hours_completed = $entity->trainingHoursCompleted;
        $model->practical_hours_completed = $entity->practicalHoursCompleted;
        $model->required_training_hours = $entity->requiredTrainingHours;
        $model->required_practical_hours = $entity->requiredPracticalHours;
        $model->progress_percentage = $entity->progressPercentage;
        $model->status = $entity->status;
        $model->prerequisites = $entity->prerequisites;
        $model->prerequisites_met = $entity->prerequisitesMet;
        $model->training_modules = $entity->trainingModules;
        $model->practical_tasks = $entity->practicalTasks;
        $model->mentor_id = $entity->mentorId;
        $model->goals = $entity->goals;
        $model->obstacles = $entity->obstacles;
        $model->correlation_id = $entity->correlationId;
        $model->tags = $entity->tags;

        return $model;
    }
}
