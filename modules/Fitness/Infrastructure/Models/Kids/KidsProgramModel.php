<?php

declare(strict_types=1);

namespace Modules\Fitness\Infrastructure\Models\Kids;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Fitness\Domain\Kids\Entities\KidsProgram;

final class KidsProgramModel extends Model
{
    use SoftDeletes;

    protected $table = 'fitness_kids_programs';

    protected $fillable = [
        'tenant_id',
        'name',
        'description',
        'target_age_group',
        'duration_weeks',
        'sessions_per_week',
        'session_duration_minutes',
        'price',
        'max_participants',
        'activities',
        'safety_guidelines',
        'is_active',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'activities' => 'array',
        'safety_guidelines' => 'array',
        'is_active' => 'boolean',
    ];

    public static function fromDomain(KidsProgram $program): self
    {
        return new self([
            'id' => $program->id > 0 ? $program->id : null,
            'tenant_id' => $program->tenantId,
            'name' => $program->name,
            'description' => $program->description,
            'target_age_group' => $program->targetAgeGroup,
            'duration_weeks' => $program->durationWeeks,
            'sessions_per_week' => $program->sessionsPerWeek,
            'session_duration_minutes' => $program->sessionDurationMinutes,
            'price' => $program->price,
            'max_participants' => $program->maxParticipants,
            'activities' => $program->activities,
            'safety_guidelines' => $program->safetyGuidelines,
            'is_active' => $program->isActive,
        ]);
    }

    public function updateFromDomain(KidsProgram $program): void
    {
        $this->name = $program->name;
        $this->description = $program->description;
        $this->target_age_group = $program->targetAgeGroup;
        $this->duration_weeks = $program->durationWeeks;
        $this->sessions_per_week = $program->sessionsPerWeek;
        $this->session_duration_minutes = $program->sessionDurationMinutes;
        $this->price = $program->price;
        $this->max_participants = $program->maxParticipants;
        $this->activities = $program->activities;
        $this->safety_guidelines = $program->safetyGuidelines;
        $this->is_active = $program->isActive;
    }

    public function toDomain(): KidsProgram
    {
        return new KidsProgram(
            id: $this->id,
            tenantId: $this->tenant_id,
            name: $this->name,
            description: $this->description,
            targetAgeGroup: $this->target_age_group,
            durationWeeks: $this->duration_weeks,
            sessionsPerWeek: $this->sessions_per_week,
            sessionDurationMinutes: $this->session_duration_minutes,
            price: (float) $this->price,
            maxParticipants: $this->max_participants,
            activities: $this->activities,
            safetyGuidelines: $this->safety_guidelines,
            isActive: $this->is_active,
            createdAt: \Carbon\CarbonImmutable::parse($this->created_at),
            updatedAt: \Carbon\CarbonImmutable::parse($this->updated_at),
        );
    }
}
