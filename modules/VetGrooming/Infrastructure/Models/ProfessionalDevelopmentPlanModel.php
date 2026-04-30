<?php

declare(strict_types=1);

namespace Modules\VetGrooming\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\VetGrooming\Domain\Entities\ProfessionalDevelopmentPlan;
use Carbon\CarbonImmutable;

class ProfessionalDevelopmentPlanModel extends Model
{
    use SoftDeletes;

    protected $table = 'professional_development_plans';

    protected $fillable = [
        'uuid',
        'tenant_id',
        'master_id',
        'profession_type',
        'current_level',
        'target_level',
        'plan_start_date',
        'plan_end_date',
        'next_review_date',
        'total_courses_required',
        'courses_completed',
        'completion_percentage',
        'effectiveness_score',
        'development_score',
        'status',
        'career_goals',
        'skill_gaps',
        'recommended_courses',
        'correlation_id',
        'tags',
    ];

    protected $casts = [
        'plan_start_date' => 'date',
        'plan_end_date' => 'date',
        'next_review_date' => 'date',
        'completion_percentage' => 'decimal:2',
        'effectiveness_score' => 'decimal:2',
        'development_score' => 'decimal:2',
        'recommended_courses' => 'array',
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

    public function toDomain(): ProfessionalDevelopmentPlan
    {
        return new ProfessionalDevelopmentPlan(
            id: $this->id,
            uuid: $this->uuid,
            tenantId: $this->tenant_id,
            masterId: $this->master_id,
            professionType: $this->profession_type,
            currentLevel: $this->current_level,
            targetLevel: $this->target_level,
            planStartDate: CarbonImmutable::parse($this->plan_start_date),
            planEndDate: CarbonImmutable::parse($this->plan_end_date),
            nextReviewDate: CarbonImmutable::parse($this->next_review_date),
            totalCoursesRequired: $this->total_courses_required,
            coursesCompleted: $this->courses_completed,
            completionPercentage: (float) $this->completion_percentage,
            effectivenessScore: $this->effectiveness_score !== null ? (float) $this->effectiveness_score : null,
            developmentScore: $this->development_score !== null ? (float) $this->development_score : null,
            status: $this->status,
            careerGoals: $this->career_goals,
            skillGaps: $this->skill_gaps,
            recommendedCourses: $this->recommended_courses,
            correlationId: $this->correlation_id,
            tags: $this->tags,
            createdAt: CarbonImmutable::parse($this->created_at),
            updatedAt: CarbonImmutable::parse($this->updated_at),
            deletedAt: $this->deleted_at ? CarbonImmutable::parse($this->deleted_at) : null,
        );
    }

    public static function fromDomain(ProfessionalDevelopmentPlan $entity): self
    {
        $model = new self();
        $model->id = $entity->id;
        $model->uuid = $entity->uuid;
        $model->tenant_id = $entity->tenantId;
        $model->master_id = $entity->masterId;
        $model->profession_type = $entity->professionType;
        $model->current_level = $entity->currentLevel;
        $model->target_level = $entity->targetLevel;
        $model->plan_start_date = $entity->planStartDate->toDateString();
        $model->plan_end_date = $entity->planEndDate->toDateString();
        $model->next_review_date = $entity->nextReviewDate->toDateString();
        $model->total_courses_required = $entity->totalCoursesRequired;
        $model->courses_completed = $entity->coursesCompleted;
        $model->completion_percentage = $entity->completionPercentage;
        $model->effectiveness_score = $entity->effectivenessScore;
        $model->development_score = $entity->developmentScore;
        $model->status = $entity->status;
        $model->career_goals = $entity->careerGoals;
        $model->skill_gaps = $entity->skillGaps;
        $model->recommended_courses = $entity->recommendedCourses;
        $model->correlation_id = $entity->correlationId;
        $model->tags = $entity->tags;

        return $model;
    }
}
