<?php

declare(strict_types=1);

namespace Modules\Fitness\Infrastructure\Models\SeasonalPrograms;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Fitness\Domain\SeasonalPrograms\Entities\SeasonalProgram;
use Modules\Fitness\Domain\SeasonalPrograms\Enums\SeasonalProgramStatus;
use Modules\Fitness\Domain\SeasonalPrograms\Enums\SeasonalProgramType;

final class SeasonalProgramModel extends Model
{
    use SoftDeletes;

    protected $table = 'fitness_seasonal_programs';

    protected $fillable = [
        'tenant_id',
        'name',
        'description',
        'type',
        'period_start',
        'period_end',
        'duration_weeks',
        'sessions_per_week',
        'price',
        'max_participants',
        'goals',
        'requirements',
        'status',
        'current_participants',
    ];

    protected $casts = [
        'period_start' => 'datetime',
        'period_end' => 'datetime',
        'duration_weeks' => 'integer',
        'sessions_per_week' => 'integer',
        'price' => 'float',
        'max_participants' => 'integer',
        'goals' => 'array',
        'current_participants' => 'integer',
    ];

    public function enrollments(): HasMany
    {
        return $this->hasMany(ClientProgramEnrollmentModel::class, 'seasonal_program_id');
    }

    public function toDomain(): SeasonalProgram
    {
        return new SeasonalProgram(
            id: $this->id,
            tenantId: $this->tenant_id,
            name: $this->name,
            description: $this->description,
            type: SeasonalProgramType::from($this->type),
            periodStart: \Carbon\CarbonImmutable::parse($this->period_start),
            periodEnd: \Carbon\CarbonImmutable::parse($this->period_end),
            durationWeeks: $this->duration_weeks,
            sessionsPerWeek: $this->sessions_per_week,
            price: $this->price,
            maxParticipants: $this->max_participants,
            goals: $this->goals,
            requirements: $this->requirements,
            status: SeasonalProgramStatus::from($this->status),
            currentParticipants: $this->current_participants,
            createdAt: \Carbon\CarbonImmutable::parse($this->created_at),
            updatedAt: \Carbon\CarbonImmutable::parse($this->updated_at),
        );
    }

    public static function fromDomain(SeasonalProgram $entity): self
    {
        return new self([
            'id' => $entity->id > 0 ? $entity->id : null,
            'tenant_id' => $entity->tenantId,
            'name' => $entity->name,
            'description' => $entity->description,
            'type' => $entity->type->value,
            'period_start' => $entity->periodStart,
            'period_end' => $entity->periodEnd,
            'duration_weeks' => $entity->durationWeeks,
            'sessions_per_week' => $entity->sessionsPerWeek,
            'price' => $entity->price,
            'max_participants' => $entity->maxParticipants,
            'goals' => $entity->goals,
            'requirements' => $entity->requirements,
            'status' => $entity->status->value,
            'current_participants' => $entity->currentParticipants,
        ]);
    }
}
