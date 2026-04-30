<?php

declare(strict_types=1);

namespace Modules\VetGrooming\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\VetGrooming\Domain\Entities\ExoticTrainingCourse;
use Modules\VetGrooming\Domain\Enums\CertificationLevel;
use Modules\VetGrooming\Domain\Enums\ExoticCategory;
use Modules\VetGrooming\Domain\Enums\ExoticGroup;

final class ExoticTrainingCourseModel extends Model
{
    protected $table = 'exotic_training_courses';

    protected $fillable = [
        'tenant_id',
        'title',
        'description',
        'target_category',
        'target_subcategory',
        'target_group',
        'target_level',
        'duration_hours',
        'is_mandatory',
        'requires_practical_exam',
        'video_url',
        'modules',
        'learning_objectives',
        'is_active',
        'version',
    ];

    protected $casts = [
        'duration_hours' => 'decimal:2',
        'is_mandatory' => 'boolean',
        'requires_practical_exam' => 'boolean',
        'modules' => 'array',
        'learning_objectives' => 'array',
        'is_active' => 'boolean',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Tenant::class, 'tenant_id');
    }

    public function toDomain(): ExoticTrainingCourse
    {
        return new ExoticTrainingCourse(
            id: $this->id,
            tenantId: $this->tenant_id,
            title: $this->title,
            description: $this->description,
            targetCategory: ExoticCategory::from($this->target_category),
            targetSubcategory: $this->target_subcategory,
            targetGroup: ExoticGroup::from($this->target_group),
            targetLevel: CertificationLevel::from($this->target_level),
            durationHours: (float) $this->duration_hours,
            isMandatory: $this->is_mandatory,
            requiresPracticalExam: $this->requires_practical_exam,
            videoUrl: $this->video_url,
            modules: $this->modules,
            learningObjectives: $this->learning_objectives,
            isActive: $this->is_active,
            version: $this->version,
            createdAt: \Carbon\CarbonImmutable::parse($this->created_at),
            updatedAt: \Carbon\CarbonImmutable::parse($this->updated_at),
        );
    }

    public static function fromDomain(ExoticTrainingCourse $course): self
    {
        return new self([
            'id' => $course->id,
            'tenant_id' => $course->tenantId,
            'title' => $course->title,
            'description' => $course->description,
            'target_category' => $course->targetCategory->value,
            'target_subcategory' => $course->targetSubcategory,
            'target_group' => $course->targetGroup->value,
            'target_level' => $course->targetLevel->value,
            'duration_hours' => $course->durationHours,
            'is_mandatory' => $course->isMandatory,
            'requires_practical_exam' => $course->requiresPracticalExam,
            'video_url' => $course->videoUrl,
            'modules' => $course->modules,
            'learning_objectives' => $course->learningObjectives,
            'is_active' => $course->isActive,
            'version' => $course->version,
            'created_at' => $course->createdAt,
            'updated_at' => $course->updatedAt,
        ]);
    }
}
