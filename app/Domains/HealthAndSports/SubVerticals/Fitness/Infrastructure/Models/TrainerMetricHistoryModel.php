<?php

declare(strict_types=1);

namespace Modules\Fitness\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Fitness\Domain\Entities\TrainerMetricHistory as TrainerMetricHistoryEntity;

final class TrainerMetricHistoryModel extends Model
{
    use SoftDeletes;

    protected $table = 'fitness_trainer_metric_history';

    protected $fillable = [
        'tenant_id',
        'business_group_id',
        'trainer_id',
        'effectiveness_id',
        'uuid',
        'correlation_id',
        'metric_name',
        'old_value',
        'new_value',
        'change_delta',
        'change_reason',
        'notes',
        'changed_at',
        'tags',
        'metadata',
    ];

    protected $casts = [
        'old_value' => 'decimal:2',
        'new_value' => 'decimal:2',
        'change_delta' => 'decimal:2',
        'changed_at' => 'datetime',
        'tags' => 'array',
        'metadata' => 'array',
    ];

    public function trainer(): BelongsTo
    {
        return $this->belongsTo(TrainerModel::class, 'trainer_id');
    }

    public function effectiveness(): BelongsTo
    {
        return $this->belongsTo(TrainerEffectivenessModel::class, 'effectiveness_id');
    }

    public function toDomain(): TrainerMetricHistoryEntity
    {
        return new TrainerMetricHistoryEntity(
            id: $this->id,
            tenantId: $this->tenant_id,
            businessGroupId: $this->business_group_id,
            trainerId: $this->trainer_id,
            effectivenessId: $this->effectiveness_id,
            uuid: $this->uuid,
            correlationId: $this->correlation_id,
            metricName: $this->metric_name,
            oldValue: $this->old_value,
            newValue: $this->new_value,
            changeDelta: $this->change_delta,
            changeReason: $this->change_reason,
            notes: $this->notes,
            changedAt: \Carbon\CarbonImmutable::parse($this->changed_at),
            tags: $this->tags,
            metadata: $this->metadata,
            createdAt: \Carbon\CarbonImmutable::parse($this->created_at),
            updatedAt: \Carbon\CarbonImmutable::parse($this->updated_at),
        );
    }

    public static function fromDomain(TrainerMetricHistoryEntity $entity): self
    {
        return new self([
            'id' => $entity->id > 0 ? $entity->id : null,
            'tenant_id' => $entity->tenantId,
            'business_group_id' => $entity->businessGroupId,
            'trainer_id' => $entity->trainerId,
            'effectiveness_id' => $entity->effectivenessId,
            'uuid' => $entity->uuid ?: \Illuminate\Support\Str::uuid(),
            'correlation_id' => $entity->correlationId,
            'metric_name' => $entity->metricName,
            'old_value' => $entity->oldValue,
            'new_value' => $entity->newValue,
            'change_delta' => $entity->changeDelta,
            'change_reason' => $entity->changeReason,
            'notes' => $entity->notes,
            'changed_at' => $entity->changedAt,
            'tags' => $entity->tags,
            'metadata' => $entity->metadata,
        ]);
    }
}
