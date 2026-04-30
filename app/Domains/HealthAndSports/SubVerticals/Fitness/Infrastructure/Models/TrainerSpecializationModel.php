<?php

declare(strict_types=1);

namespace Modules\Fitness\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Fitness\Domain\Entities\TrainerSpecialization as TrainerSpecializationEntity;

final class TrainerSpecializationModel extends Model
{
    use SoftDeletes;

    protected $table = 'fitness_trainer_specializations';

    protected $fillable = [
        'tenant_id',
        'business_group_id',
        'trainer_id',
        'uuid',
        'correlation_id',
        'specialization',
        'level',
        'certification_id',
        'is_active',
        'assigned_date',
        'expiry_date',
        'notes',
        'tags',
        'metadata',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'assigned_date' => 'date',
        'expiry_date' => 'date',
        'tags' => 'array',
        'metadata' => 'array',
    ];

    public function trainer(): BelongsTo
    {
        return $this->belongsTo(TrainerModel::class, 'trainer_id');
    }

    public function certification(): BelongsTo
    {
        return $this->belongsTo(TrainerCertificationModel::class, 'certification_id');
    }

    public function toDomain(): TrainerSpecializationEntity
    {
        return new TrainerSpecializationEntity(
            id: $this->id,
            tenantId: $this->tenant_id,
            businessGroupId: $this->business_group_id,
            trainerId: $this->trainer_id,
            uuid: $this->uuid,
            correlationId: $this->correlation_id,
            specialization: $this->specialization,
            level: $this->level,
            certificationId: $this->certification_id,
            isActive: $this->is_active,
            assignedDate: \Carbon\CarbonImmutable::parse($this->assigned_date),
            expiryDate: $this->expiry_date ? \Carbon\CarbonImmutable::parse($this->expiry_date) : null,
            notes: $this->notes,
            tags: $this->tags,
            metadata: $this->metadata,
            createdAt: \Carbon\CarbonImmutable::parse($this->created_at),
            updatedAt: \Carbon\CarbonImmutable::parse($this->updated_at),
        );
    }

    public static function fromDomain(TrainerSpecializationEntity $entity): self
    {
        return new self([
            'id' => $entity->id > 0 ? $entity->id : null,
            'tenant_id' => $entity->tenantId,
            'business_group_id' => $entity->businessGroupId,
            'trainer_id' => $entity->trainerId,
            'uuid' => $entity->uuid ?: \Illuminate\Support\Str::uuid(),
            'correlation_id' => $entity->correlationId,
            'specialization' => $entity->specialization,
            'level' => $entity->level,
            'certification_id' => $entity->certificationId,
            'is_active' => $entity->isActive,
            'assigned_date' => $entity->assignedDate,
            'expiry_date' => $entity->expiryDate,
            'notes' => $entity->notes,
            'tags' => $entity->tags,
            'metadata' => $entity->metadata,
        ]);
    }
}
