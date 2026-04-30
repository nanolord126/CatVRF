<?php

declare(strict_types=1);

namespace Modules\Fitness\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Fitness\Domain\Entities\WorkoutType as WorkoutTypeEntity;
use Modules\Fitness\Domain\Enums\WorkoutIntensity;

final class WorkoutTypeModel extends Model
{
    use SoftDeletes;

    protected $table = 'fitness_workout_types';

    protected $fillable = [
        'tenant_id',
        'name',
        'description',
        'intensity',
        'duration_minutes',
        'calories_burn_estimate',
        'equipment_needed',
        'category',
        'is_group',
        'max_participants',
        'price',
        'icon',
        'color',
        'is_active',
    ];

    protected $casts = [
        'duration_minutes' => 'integer',
        'calories_burn_estimate' => 'integer',
        'equipment_needed' => 'array',
        'is_group' => 'boolean',
        'max_participants' => 'integer',
        'price' => 'float',
        'is_active' => 'boolean',
    ];

    public function scheduleSlots(): HasMany
    {
        return $this->hasMany(ScheduleSlotModel::class, 'workout_type_id');
    }

    public function workoutSessions(): HasMany
    {
        return $this->hasMany(WorkoutSessionModel::class, 'workout_type_id');
    }

    public function toDomain(): WorkoutTypeEntity
    {
        return new WorkoutTypeEntity(
            id: $this->id,
            tenantId: $this->tenant_id,
            name: $this->name,
            description: $this->description,
            intensity: WorkoutIntensity::from($this->intensity),
            durationMinutes: $this->duration_minutes,
            caloriesBurnEstimate: $this->calories_burn_estimate,
            equipmentNeeded: $this->equipment_needed,
            category: $this->category,
            isGroup: $this->is_group,
            maxParticipants: $this->max_participants,
            price: $this->price,
            icon: $this->icon,
            color: $this->color,
            isActive: $this->is_active,
            createdAt: \Carbon\CarbonImmutable::parse($this->created_at),
            updatedAt: \Carbon\CarbonImmutable::parse($this->updated_at),
        );
    }

    public static function fromDomain(WorkoutTypeEntity $entity): self
    {
        return new self([
            'id' => $entity->id > 0 ? $entity->id : null,
            'tenant_id' => $entity->tenantId,
            'name' => $entity->name,
            'description' => $entity->description,
            'intensity' => $entity->intensity->value,
            'duration_minutes' => $entity->durationMinutes,
            'calories_burn_estimate' => $entity->caloriesBurnEstimate,
            'equipment_needed' => $entity->equipmentNeeded,
            'category' => $entity->category,
            'is_group' => $entity->isGroup,
            'max_participants' => $entity->maxParticipants,
            'price' => $entity->price,
            'icon' => $entity->icon,
            'color' => $entity->color,
            'is_active' => $entity->isActive,
        ]);
    }
}
