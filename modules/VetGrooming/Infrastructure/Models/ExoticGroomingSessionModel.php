<?php

declare(strict_types=1);

namespace Modules\VetGrooming\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\VetGrooming\Domain\Entities\ExoticGroomingSession;
use Modules\VetGrooming\Domain\Enums\ExoticCategory;
use Modules\VetGrooming\Domain\Enums\ExoticGroup;
use Modules\VetGrooming\Domain\Enums\ExoticProcedureType;
use Modules\VetGrooming\Domain\Enums\HandlingMethod;
use Modules\Media\Domain\Traits\HasMediaTrait;
use Carbon\CarbonImmutable;

final class ExoticGroomingSessionModel extends Model
{
    use SoftDeletes;
    use HasMediaTrait;

    protected $table = 'exotic_grooming_sessions';

    protected $fillable = [
        'pet_id',
        'groomer_id',
        'tenant_id',
        'exotic_type',
        'species_group',
        'procedure_type',
        'stress_level_before',
        'stress_level_after',
        'duration_minutes',
        'temperature_controlled',
        'room_temperature',
        'sedation_used',
        'sedation_notes',
        'handling_method',
        'protocol_checklist',
        'before_photos',
        'after_photos',
        'notes',
        'medical_notes',
        'appointment_id',
        'started_at',
        'completed_at',
        'status',
    ];

    protected $casts = [
        'stress_level_before' => 'integer',
        'stress_level_after' => 'integer',
        'duration_minutes' => 'integer',
        'temperature_controlled' => 'boolean',
        'room_temperature' => 'decimal:2',
        'sedation_used' => 'boolean',
        'protocol_checklist' => 'array',
        'before_photos' => 'array',
        'after_photos' => 'array',
        'medical_notes' => 'array',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function pet(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Pet::class, 'pet_id');
    }

    public function groomer(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Master::class, 'groomer_id');
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Tenant::class, 'tenant_id');
    }

    public function appointment(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Appointment::class, 'appointment_id');
    }

    public function toDomain(): ExoticGroomingSession
    {
        return new ExoticGroomingSession(
            id: $this->id,
            petId: $this->pet_id,
            groomerId: $this->groomer_id,
            tenantId: $this->tenant_id,
            exoticType: ExoticCategory::from($this->exotic_type),
            speciesGroup: $this->species_group,
            procedureType: ExoticProcedureType::from($this->procedure_type),
            stressLevelBefore: $this->stress_level_before,
            stressLevelAfter: $this->stress_level_after,
            durationMinutes: $this->duration_minutes,
            temperatureControlled: $this->temperature_controlled,
            roomTemperature: $this->room_temperature,
            sedationUsed: $this->sedation_used,
            sedationNotes: $this->sedation_notes,
            handlingMethod: HandlingMethod::from($this->handling_method),
            protocolChecklist: $this->protocol_checklist,
            beforePhotos: $this->before_photos,
            afterPhotos: $this->after_photos,
            notes: $this->notes,
            medicalNotes: $this->medical_notes,
            appointmentId: $this->appointment_id,
            startedAt: \Carbon\CarbonImmutable::parse($this->started_at),
            completedAt: $this->completed_at ? \Carbon\CarbonImmutable::parse($this->completed_at) : null,
            status: $this->status,
            createdAt: \Carbon\CarbonImmutable::parse($this->created_at),
            updatedAt: \Carbon\CarbonImmutable::parse($this->updated_at),
        );
    }

    public static function fromDomain(ExoticGroomingSession $session): self
    {
        return new self([
            'id' => $session->id,
            'pet_id' => $session->petId,
            'groomer_id' => $session->groomerId,
            'tenant_id' => $session->tenantId,
            'exotic_type' => $session->exoticType->value,
            'species_group' => $session->speciesGroup,
            'procedure_type' => $session->procedureType->value,
            'stress_level_before' => $session->stressLevelBefore,
            'stress_level_after' => $session->stressLevelAfter,
            'duration_minutes' => $session->durationMinutes,
            'temperature_controlled' => $session->temperatureControlled,
            'room_temperature' => $session->roomTemperature,
            'sedation_used' => $session->sedationUsed,
            'sedation_notes' => $session->sedationNotes,
            'handling_method' => $session->handlingMethod->value,
            'protocol_checklist' => $session->protocolChecklist,
            'before_photos' => $session->beforePhotos,
            'after_photos' => $session->afterPhotos,
            'notes' => $session->notes,
            'medical_notes' => $session->medicalNotes,
            'appointment_id' => $session->appointmentId,
            'started_at' => $session->startedAt,
            'completed_at' => $session->completedAt,
            'status' => $session->status,
            'created_at' => $session->createdAt,
            'updated_at' => $session->updatedAt,
        ]);
    }
}
