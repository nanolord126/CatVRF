<?php

declare(strict_types=1);

namespace Modules\Fitness\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Fitness\Domain\Entities\Trainer as TrainerEntity;
use Modules\Fitness\Infrastructure\Models\TrainerEffectivenessModel;
use Modules\Fitness\Infrastructure\Models\TrainerCertificationModel;
use Modules\Fitness\Infrastructure\Models\TrainerSpecializationModel;

final class TrainerModel extends Model
{
    use SoftDeletes;

    protected $table = 'fitness_trainers';

    protected $fillable = [
        'tenant_id',
        'user_id',
        'first_name',
        'last_name',
        'patronymic',
        'specialization',
        'certifications',
        'rating',
        'total_sessions',
        'bio',
        'photo_url',
        'working_hours',
        'hourly_rate',
        'is_available',
    ];

    protected $casts = [
        'rating' => 'float',
        'total_sessions' => 'integer',
        'certifications' => 'array',
        'working_hours' => 'array',
        'hourly_rate' => 'float',
        'is_available' => 'boolean',
        'is_on_hold' => 'boolean',
        'on_hold_since' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'user_id');
    }

    public function scheduleSlots(): HasMany
    {
        return $this->hasMany(ScheduleSlotModel::class, 'trainer_id');
    }

    public function workoutSessions(): HasMany
    {
        return $this->hasMany(WorkoutSessionModel::class, 'trainer_id');
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(AttendanceModel::class, 'trainer_id');
    }

    public function effectiveness(): HasMany
    {
        return $this->hasMany(TrainerEffectivenessModel::class, 'trainer_id');
    }

    public function trainerCertifications(): HasMany
    {
        return $this->hasMany(TrainerCertificationModel::class, 'trainer_id');
    }

    public function trainerSpecializations(): HasMany
    {
        return $this->hasMany(TrainerSpecializationModel::class, 'trainer_id');
    }

    public function toDomain(): TrainerEntity
    {
        return new TrainerEntity(
            id: $this->id,
            tenantId: $this->tenant_id,
            userId: $this->user_id,
            firstName: $this->first_name,
            lastName: $this->last_name,
            patronymic: $this->patronymic,
            specialization: $this->specialization,
            certifications: $this->certifications,
            rating: $this->rating,
            totalSessions: $this->total_sessions,
            bio: $this->bio,
            photoUrl: $this->photo_url,
            workingHours: $this->working_hours,
            hourlyRate: $this->hourly_rate,
            isAvailable: $this->is_available,
            createdAt: \Carbon\CarbonImmutable::parse($this->created_at),
            updatedAt: \Carbon\CarbonImmutable::parse($this->updated_at),
        );
    }

    public static function fromDomain(TrainerEntity $entity): self
    {
        return new self([
            'id' => $entity->id > 0 ? $entity->id : null,
            'tenant_id' => $entity->tenantId,
            'user_id' => $entity->userId,
            'first_name' => $entity->firstName,
            'last_name' => $entity->lastName,
            'patronymic' => $entity->patronymic,
            'specialization' => $entity->specialization,
            'certifications' => $entity->certifications,
            'rating' => $entity->rating,
            'total_sessions' => $entity->totalSessions,
            'bio' => $entity->bio,
            'photo_url' => $entity->photoUrl,
            'working_hours' => $entity->workingHours,
            'hourly_rate' => $entity->hourlyRate,
            'is_available' => $entity->isAvailable,
        ]);
    }
}
