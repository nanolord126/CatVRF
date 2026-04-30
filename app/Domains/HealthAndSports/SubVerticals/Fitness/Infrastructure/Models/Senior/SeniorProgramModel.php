<?php

declare(strict_types=1);

namespace Modules\Fitness\Infrastructure\Models\Senior;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Fitness\Domain\Senior\Entities\SeniorProgram;

final class SeniorProgramModel extends Model
{
    use SoftDeletes;

    protected $table = 'fitness_senior_programs';

    protected $fillable = [
        'tenant_id',
        'name',
        'description',
        'focus_area',
        'duration_weeks',
        'sessions_per_week',
        'session_duration_minutes',
        'price',
        'max_participants',
        'exercises',
        'safety_requirements',
        'is_active',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'exercises' => 'array',
        'safety_requirements' => 'array',
        'is_active' => 'boolean',
    ];

    public static function fromDomain(SeniorProgram $program): self
    {
        return new self([
            'id' => $program->id > 0 ? $program->id : null,
            'tenant_id' => $program->tenantId,
            'name' => $program->name,
            'description' => $program->description,
            'focus_area' => $program->focusArea,
            'duration_weeks' => $program->durationWeeks,
            'sessions_per_week' => $program->sessionsPerWeek,
            'session_duration_minutes' => $program->sessionDurationMinutes,
            'price' => $program->price,
            'max_participants' => $program->maxParticipants,
            'exercises' => $program->exercises,
            'safety_requirements' => $program->safetyRequirements,
            'is_active' => $program->isActive,
        ]);
    }

    public function updateFromDomain(SeniorProgram $program): void
    {
        $this->name = $program->name;
        $this->description = $program->description;
        $this->focus_area = $program->focusArea;
        $this->duration_weeks = $program->durationWeeks;
        $this->sessions_per_week = $program->sessionsPerWeek;
        $this->session_duration_minutes = $program->sessionDurationMinutes;
        $this->price = $program->price;
        $this->max_participants = $program->maxParticipants;
        $this->exercises = $program->exercises;
        $this->safety_requirements = $program->safetyRequirements;
        $this->is_active = $program->isActive;
    }

    public function toDomain(): SeniorProgram
    {
        return new SeniorProgram(
            id: $this->id,
            tenantId: $this->tenant_id,
            name: $this->name,
            description: $this->description,
            focusArea: $this->focus_area,
            durationWeeks: $this->duration_weeks,
            sessionsPerWeek: $this->sessions_per_week,
            sessionDurationMinutes: $this->session_duration_minutes,
            price: (float) $this->price,
            maxParticipants: $this->max_participants,
            exercises: $this->exercises,
            safetyRequirements: $this->safety_requirements,
            isActive: $this->is_active,
            createdAt: \Carbon\CarbonImmutable::parse($this->created_at),
            updatedAt: \Carbon\CarbonImmutable::parse($this->updated_at),
        );
    }
}
