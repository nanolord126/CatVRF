<?php

declare(strict_types=1);

namespace Modules\Fitness\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Fitness\Domain\Entities\TrainerEffectiveness as TrainerEffectivenessEntity;

final class TrainerEffectivenessModel extends Model
{
    use SoftDeletes;

    protected $table = 'fitness_trainer_effectiveness';

    protected $fillable = [
        'tenant_id',
        'business_group_id',
        'trainer_id',
        'uuid',
        'correlation_id',
        'retention_rate',
        'nps_score',
        'avg_check_per_client',
        'repeat_bookings_count',
        'churn_rate',
        'occupancy_rate',
        'avg_group_attendance',
        'individual_sessions_count',
        'schedule_compliance',
        'manager_score',
        'methodology_compliance',
        'progress_photos_count',
        'total_score',
        'effectiveness_level',
        'period_start',
        'period_end',
        'recommendations',
        'tags',
        'metadata',
    ];

    protected $casts = [
        'retention_rate' => 'decimal:2',
        'nps_score' => 'decimal:2',
        'avg_check_per_client' => 'decimal:2',
        'repeat_bookings_count' => 'integer',
        'churn_rate' => 'decimal:2',
        'occupancy_rate' => 'decimal:2',
        'avg_group_attendance' => 'decimal:2',
        'individual_sessions_count' => 'integer',
        'schedule_compliance' => 'decimal:2',
        'manager_score' => 'decimal:2',
        'methodology_compliance' => 'boolean',
        'progress_photos_count' => 'integer',
        'total_score' => 'decimal:2',
        'period_start' => 'date',
        'period_end' => 'date',
        'tags' => 'array',
        'metadata' => 'array',
    ];

    public function trainer(): BelongsTo
    {
        return $this->belongsTo(TrainerModel::class, 'trainer_id');
    }
: HasMany
    public function metricHistories()
    {
        return $this->hasMany(TrainerMetricHistoryModel::class, 'effectiveness_id');
    }

    public function toDomain(): TrainerEffectivenessEntity
    {
        return new TrainerEffectivenessEntity(
            id: $this->id,
            tenantId: $this->tenant_id,
            businessGroupId: $this->business_group_id,
            trainerId: $this->trainer_id,
            uuid: $this->uuid,
            correlationId: $this->correlation_id,
            retentionRate: $this->retention_rate,
            npsScore: $this->nps_score,
            avgCheckPerClient: $this->avg_check_per_client,
            repeatBookingsCount: $this->repeat_bookings_count,
            churnRate: $this->churn_rate,
            occupancyRate: $this->occupancy_rate,
            avgGroupAttendance: $this->avg_group_attendance,
            individualSessionsCount: $this->individual_sessions_count,
            scheduleCompliance: $this->schedule_compliance,
            managerScore: $this->manager_score,
            methodologyCompliance: $this->methodology_compliance,
            progressPhotosCount: $this->progress_photos_count,
            totalScore: $this->total_score,
            effectivenessLevel: $this->effectiveness_level,
            periodStart: \Carbon\CarbonImmutable::parse($this->period_start),
            periodEnd: \Carbon\CarbonImmutable::parse($this->period_end),
            recommendations: $this->recommendations,
            tags: $this->tags,
            metadata: $this->metadata,
            createdAt: \Carbon\CarbonImmutable::parse($this->created_at),
            updatedAt: \Carbon\CarbonImmutable::parse($this->updated_at),
        );
    }

    public static function fromDomain(TrainerEffectivenessEntity $entity): self
    {
        return new self([
            'id' => $entity->id > 0 ? $entity->id : null,
            'tenant_id' => $entity->tenantId,
            'business_group_id' => $entity->businessGroupId,
            'trainer_id' => $entity->trainerId,
            'uuid' => $entity->uuid ?: \Illuminate\Support\Str::uuid(),
            'correlation_id' => $entity->correlationId,
            'retention_rate' => $entity->retentionRate,
            'nps_score' => $entity->npsScore,
            'avg_check_per_client' => $entity->avgCheckPerClient,
            'repeat_bookings_count' => $entity->repeatBookingsCount,
            'churn_rate' => $entity->churnRate,
            'occupancy_rate' => $entity->occupancyRate,
            'avg_group_attendance' => $entity->avgGroupAttendance,
            'individual_sessions_count' => $entity->individualSessionsCount,
            'schedule_compliance' => $entity->scheduleCompliance,
            'manager_score' => $entity->managerScore,
            'methodology_compliance' => $entity->methodologyCompliance,
            'progress_photos_count' => $entity->progressPhotosCount,
            'total_score' => $entity->totalScore,
            'effectiveness_level' => $entity->effectivenessLevel,
            'period_start' => $entity->periodStart,
            'period_end' => $entity->periodEnd,
            'recommendations' => $entity->recommendations,
            'tags' => $entity->tags,
            'metadata' => $entity->metadata,
        ]);
    }
}
