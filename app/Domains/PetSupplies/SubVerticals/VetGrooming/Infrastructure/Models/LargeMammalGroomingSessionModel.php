<?php

declare(strict_types=1);

namespace Modules\VetGrooming\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\VetGrooming\Domain\Entities\LargeMammalGroomingSession;
use Modules\VetGrooming\Domain\Enums\ExoticProcedureType;
use Modules\VetGrooming\Domain\Enums\HandlingMethod;

final class LargeMammalGroomingSessionModel extends Model
{
    protected $table = 'large_mammal_grooming_sessions';

    protected $fillable = [
        'pet_id',
        'groomer_id',
        'tenant_id',
        'mammal_group',
        'procedure_type',
        'aggression_level',
        'stress_level_before',
        'stress_level_after',
        'restraint_method',
        'safety_incident',
        'safety_incident_description',
        'second_groomer_id',
        'veterinarian_id',
        'duration_minutes',
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
        'aggression_level' => 'integer',
        'stress_level_before' => 'integer',
        'stress_level_after' => 'integer',
        'safety_incident' => 'boolean',
        'duration_minutes' => 'integer',
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

    public function secondGroomer(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Master::class, 'second_groomer_id');
    }

    public function veterinarian(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'veterinarian_id');
    }

    public function appointment(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Appointment::class, 'appointment_id');
    }

    public function toDomain(): LargeMammalGroomingSession
    {
        return new LargeMammalGroomingSession(
            id: $this->id,
            petId: $this->pet_id,
            groomerId: $this->groomer_id,
            tenantId: $this->tenant_id,
            mammalGroup: $this->mammal_group,
            procedureType: ExoticProcedureType::from($this->procedure_type),
            aggressionLevel: $this->aggression_level,
            stressLevelBefore: $this->stress_level_before,
            stressLevelAfter: $this->stress_level_after,
            restraintMethod: HandlingMethod::from($this->restraint_method),
            safetyIncident: $this->safety_incident,
            safetyIncidentDescription: $this->safety_incident_description,
            secondGroomerId: $this->second_groomer_id,
            veterinarianId: $this->veterinarian_id,
            durationMinutes: $this->duration_minutes,
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

    public static function fromDomain(LargeMammalGroomingSession $session): self
    {
        return new self([
            'id' => $session->id,
            'pet_id' => $session->petId,
            'groomer_id' => $session->groomerId,
            'tenant_id' => $session->tenantId,
            'mammal_group' => $session->mammalGroup,
            'procedure_type' => $session->procedureType->value,
            'aggression_level' => $session->aggressionLevel,
            'stress_level_before' => $session->stressLevelBefore,
            'stress_level_after' => $session->stressLevelAfter,
            'restraint_method' => $session->restraintMethod->value,
            'safety_incident' => $session->safetyIncident,
            'safety_incident_description' => $session->safetyIncidentDescription,
            'second_groomer_id' => $session->secondGroomerId,
            'veterinarian_id' => $session->veterinarianId,
            'duration_minutes' => $session->durationMinutes,
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
