<?php

declare(strict_types=1);

namespace Modules\Fitness\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Fitness\Domain\Entities\WorkoutSession as WorkoutSessionEntity;

final class WorkoutSessionModel extends Model
{
    use SoftDeletes;

    protected $table = 'fitness_workout_sessions';

    protected $fillable = [
        'tenant_id',
        'schedule_slot_id',
        'trainer_id',
        'venue_id',
        'workout_type_id',
        'start_time',
        'end_time',
        'actual_participants',
        'trainer_notes',
        'exercises_performed',
        'average_rating',
        'total_ratings',
        'equipment_used',
        'music_playlist',
        'temperature',
        'humidity',
    ];

    protected $casts = [
        'start_time' => 'datetime',
        'end_time' => 'datetime',
        'actual_participants' => 'integer',
        'exercises_performed' => 'array',
        'average_rating' => 'float',
        'total_ratings' => 'integer',
        'equipment_used' => 'array',
        'temperature' => 'float',
        'humidity' => 'float',
    ];

    public function scheduleSlot(): BelongsTo
    {
        return $this->belongsTo(ScheduleSlotModel::class, 'schedule_slot_id');
    }

    public function trainer(): BelongsTo
    {
        return $this->belongsTo(TrainerModel::class, 'trainer_id');
    }

    public function venue(): BelongsTo
    {
        return $this->belongsTo(VenueModel::class, 'venue_id');
    }

    public function workoutType(): BelongsTo
    {
        return $this->belongsTo(WorkoutTypeModel::class, 'workout_type_id');
    }

    public function toDomain(): WorkoutSessionEntity
    {
        return new WorkoutSessionEntity(
            id: $this->id,
            tenantId: $this->tenant_id,
            scheduleSlotId: $this->schedule_slot_id,
            trainerId: $this->trainer_id,
            venueId: $this->venue_id,
            workoutTypeId: $this->workout_type_id,
            startTime: \Carbon\CarbonImmutable::parse($this->start_time),
            endTime: \Carbon\CarbonImmutable::parse($this->end_time),
            actualParticipants: $this->actual_participants,
            trainerNotes: $this->trainer_notes,
            exercisesPerformed: $this->exercises_performed,
            averageRating: $this->average_rating,
            totalRatings: $this->total_ratings,
            equipmentUsed: $this->equipment_used,
            musicPlaylist: $this->music_playlist,
            temperature: $this->temperature,
            humidity: $this->humidity,
            createdAt: \Carbon\CarbonImmutable::parse($this->created_at),
            updatedAt: \Carbon\CarbonImmutable::parse($this->updated_at),
        );
    }

    public static function fromDomain(WorkoutSessionEntity $entity): self
    {
        return new self([
            'id' => $entity->id > 0 ? $entity->id : null,
            'tenant_id' => $entity->tenantId,
            'schedule_slot_id' => $entity->scheduleSlotId,
            'trainer_id' => $entity->trainerId,
            'venue_id' => $entity->venueId,
            'workout_type_id' => $entity->workoutTypeId,
            'start_time' => $entity->startTime,
            'end_time' => $entity->endTime,
            'actual_participants' => $entity->actualParticipants,
            'trainer_notes' => $entity->trainerNotes,
            'exercises_performed' => $entity->exercisesPerformed,
            'average_rating' => $entity->averageRating,
            'total_ratings' => $entity->totalRatings,
            'equipment_used' => $entity->equipmentUsed,
            'music_playlist' => $entity->musicPlaylist,
            'temperature' => $entity->temperature,
            'humidity' => $entity->humidity,
        ]);
    }
}
