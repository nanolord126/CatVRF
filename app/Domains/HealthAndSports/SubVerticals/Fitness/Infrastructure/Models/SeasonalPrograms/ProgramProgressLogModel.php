<?php

declare(strict_types=1);

namespace Modules\Fitness\Infrastructure\Models\SeasonalPrograms;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Fitness\Domain\SeasonalPrograms\Entities\ProgramProgressLog;

final class ProgramProgressLogModel extends Model
{
    use SoftDeletes;

    protected $table = 'fitness_program_progress_logs';

    protected $fillable = [
        'tenant_id',
        'enrollment_id',
        'date',
        'metrics',
        'notes',
        'photos',
        'weight',
        'body_fat_percentage',
        'measurements',
        'wellbeing_score',
    ];

    protected $casts = [
        'date' => 'datetime',
        'metrics' => 'array',
        'photos' => 'array',
        'weight' => 'float',
        'body_fat_percentage' => 'float',
        'measurements' => 'array',
        'wellbeing_score' => 'integer',
    ];

    public function enrollment(): BelongsTo
    {
        return $this->belongsTo(ClientProgramEnrollmentModel::class, 'enrollment_id');
    }

    public function toDomain(): ProgramProgressLog
    {
        return new ProgramProgressLog(
            id: $this->id,
            tenantId: $this->tenant_id,
            enrollmentId: $this->enrollment_id,
            date: \Carbon\CarbonImmutable::parse($this->date),
            metrics: $this->metrics,
            notes: $this->notes,
            photos: $this->photos,
            weight: $this->weight,
            bodyFatPercentage: $this->body_fat_percentage,
            measurements: $this->measurements,
            wellbeingScore: $this->wellbeing_score,
            createdAt: \Carbon\CarbonImmutable::parse($this->created_at),
            updatedAt: \Carbon\CarbonImmutable::parse($this->updated_at),
        );
    }

    public static function fromDomain(ProgramProgressLog $entity): self
    {
        return new self([
            'id' => $entity->id > 0 ? $entity->id : null,
            'tenant_id' => $entity->tenantId,
            'enrollment_id' => $entity->enrollmentId,
            'date' => $entity->date,
            'metrics' => $entity->metrics,
            'notes' => $entity->notes,
            'photos' => $entity->photos,
            'weight' => $entity->weight,
            'body_fat_percentage' => $entity->bodyFatPercentage,
            'measurements' => $entity->measurements,
            'wellbeing_score' => $entity->wellbeingScore,
        ]);
    }
}
