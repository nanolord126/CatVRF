<?php

declare(strict_types=1);

namespace Modules\Fitness\Infrastructure\Models\SeasonalPrograms;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Fitness\Domain\SeasonalPrograms\Entities\ClientProgramEnrollment;
use Modules\Fitness\Domain\SeasonalPrograms\Enums\EnrollmentStatus;

final class ClientProgramEnrollmentModel extends Model
{
    use SoftDeletes;

    protected $table = 'fitness_client_program_enrollments';

    protected $fillable = [
        'tenant_id',
        'client_id',
        'seasonal_program_id',
        'start_date',
        'progress_percent',
        'status',
        'initial_metrics',
        'final_metrics',
        'notes',
    ];

    protected $casts = [
        'start_date' => 'datetime',
        'progress_percent' => 'float',
        'initial_metrics' => 'array',
        'final_metrics' => 'array',
    ];

    public function seasonalProgram(): BelongsTo
    {
        return $this->belongsTo(SeasonalProgramModel::class, 'seasonal_program_id');
    }

    public function progressLogs(): HasMany
    {
        return $this->hasMany(ProgramProgressLogModel::class, 'enrollment_id');
    }

    public function toDomain(): ClientProgramEnrollment
    {
        return new ClientProgramEnrollment(
            id: $this->id,
            tenantId: $this->tenant_id,
            clientId: $this->client_id,
            seasonalProgramId: $this->seasonal_program_id,
            startDate: \Carbon\CarbonImmutable::parse($this->start_date),
            progressPercent: $this->progress_percent,
            status: EnrollmentStatus::from($this->status),
            initialMetrics: $this->initial_metrics,
            finalMetrics: $this->final_metrics,
            notes: $this->notes,
            createdAt: \Carbon\CarbonImmutable::parse($this->created_at),
            updatedAt: \Carbon\CarbonImmutable::parse($this->updated_at),
        );
    }

    public static function fromDomain(ClientProgramEnrollment $entity): self
    {
        return new self([
            'id' => $entity->id > 0 ? $entity->id : null,
            'tenant_id' => $entity->tenantId,
            'client_id' => $entity->clientId,
            'seasonal_program_id' => $entity->seasonalProgramId,
            'start_date' => $entity->startDate,
            'progress_percent' => $entity->progressPercent,
            'status' => $entity->status->value,
            'initial_metrics' => $entity->initialMetrics,
            'final_metrics' => $entity->finalMetrics,
            'notes' => $entity->notes,
        ]);
    }
}
