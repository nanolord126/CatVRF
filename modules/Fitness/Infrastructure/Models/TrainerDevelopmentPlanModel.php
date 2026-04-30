<?php

declare(strict_types=1);

namespace Modules\Fitness\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Fitness\Domain\Entities\TrainerDevelopmentPlan as TrainerDevelopmentPlanEntity;

final class TrainerDevelopmentPlanModel extends Model
{
    use SoftDeletes;

    protected $table = 'fitness_trainer_development_plans';

    protected $fillable = [
        'tenant_id',
        'business_group_id',
        'trainer_id',
        'uuid',
        'correlation_id',
        'goal',
        'description',
        'required_courses',
        'progress_percentage',
        'status',
        'start_date',
        'target_date',
        'completed_date',
        'mentor_id',
        'notes',
        'tags',
        'metadata',
    ];

    protected $casts = [
        'required_courses' => 'array',
        'progress_percentage' => 'integer',
        'start_date' => 'date',
        'target_date' => 'date',
        'completed_date' => 'date',
        'tags' => 'array',
        'metadata' => 'array',
    ];

    public function trainer(): BelongsTo
    {
        return $this->belongsTo(TrainerModel::class, 'trainer_id');
    }

    public function mentor(): BelongsTo
    {
        return $this->belongsTo(TrainerModel::class, 'mentor_id');
    }

    public function toDomain(): TrainerDevelopmentPlanEntity
    {
        return new TrainerDevelopmentPlanEntity(
            id: $this->id,
            tenantId: $this->tenant_id,
            businessGroupId: $this->business_group_id,
            trainerId: $this->trainer_id,
            uuid: $this->uuid,
            correlationId: $this->correlation_id,
            goal: $this->goal,
            description: $this->description,
            requiredCourses: $this->required_courses,
            progressPercentage: $this->progress_percentage,
            status: $this->status,
            startDate: \Carbon\CarbonImmutable::parse($this->start_date),
            targetDate: \Carbon\CarbonImmutable::parse($this->target_date),
            completedDate: $this->completed_date ? \Carbon\CarbonImmutable::parse($this->completed_date) : null,
            mentorId: $this->mentor_id,
            notes: $this->notes,
            tags: $this->tags,
            metadata: $this->metadata,
            createdAt: \Carbon\CarbonImmutable::parse($this->created_at),
            updatedAt: \Carbon\CarbonImmutable::parse($this->updated_at),
        );
    }

    public static function fromDomain(TrainerDevelopmentPlanEntity $entity): self
    {
        return new self([
            'id' => $entity->id > 0 ? $entity->id : null,
            'tenant_id' => $entity->tenantId,
            'business_group_id' => $entity->businessGroupId,
            'trainer_id' => $entity->trainerId,
            'uuid' => $entity->uuid ?: \Illuminate\Support\Str::uuid(),
            'correlation_id' => $entity->correlationId,
            'goal' => $entity->goal,
            'description' => $entity->description,
            'required_courses' => $entity->requiredCourses,
            'progress_percentage' => $entity->progressPercentage,
            'status' => $entity->status,
            'start_date' => $entity->startDate,
            'target_date' => $entity->targetDate,
            'completed_date' => $entity->completedDate,
            'mentor_id' => $entity->mentorId,
            'notes' => $entity->notes,
            'tags' => $entity->tags,
            'metadata' => $entity->metadata,
        ]);
    }
}
