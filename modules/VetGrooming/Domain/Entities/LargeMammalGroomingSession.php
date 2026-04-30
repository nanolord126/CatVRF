<?php

declare(strict_types=1);

namespace Modules\VetGrooming\Domain\Entities;

use Modules\VetGrooming\Domain\Enums\ExoticProcedureType;
use Modules\VetGrooming\Domain\Enums\HandlingMethod;
use Carbon\CarbonImmutable;

final readonly class LargeMammalGroomingSession
{
    public function __construct(
        public int $id,
        public int $petId,
        public int $groomerId,
        public int $tenantId,
        public string $mammalGroup,
        public ExoticProcedureType $procedureType,
        public int $aggressionLevel,
        public int $stressLevelBefore,
        public int $stressLevelAfter,
        public HandlingMethod $restraintMethod,
        public bool $safetyIncident,
        public ?string $safetyIncidentDescription,
        public ?int $secondGroomerId,
        public ?int $veterinarianId,
        public int $durationMinutes,
        public ?array $protocolChecklist,
        public ?array $beforePhotos,
        public ?array $afterPhotos,
        public ?string $notes,
        public ?array $medicalNotes,
        public ?int $appointmentId,
        public CarbonImmutable $startedAt,
        public ?CarbonImmutable $completedAt,
        public string $status,
        public CarbonImmutable $createdAt,
        public CarbonImmutable $updatedAt,
    ) {}

    public static function create(
        int $petId,
        int $groomerId,
        int $tenantId,
        string $mammalGroup,
        ExoticProcedureType $procedureType,
        int $aggressionLevel,
        int $stressLevelBefore,
        HandlingMethod $restraintMethod,
        ?int $appointmentId = null,
    ): self {
        return new self(
            id: 0,
            petId: $petId,
            groomerId: $groomerId,
            tenantId: $tenantId,
            mammalGroup: $mammalGroup,
            procedureType: $procedureType,
            aggressionLevel: $aggressionLevel,
            stressLevelBefore: $stressLevelBefore,
            stressLevelAfter: 0,
            restraintMethod: $restraintMethod,
            safetyIncident: false,
            safetyIncidentDescription: null,
            secondGroomerId: null,
            veterinarianId: null,
            durationMinutes: 0,
            protocolChecklist: null,
            beforePhotos: null,
            afterPhotos: null,
            notes: null,
            medicalNotes: null,
            appointmentId: $appointmentId,
            startedAt: CarbonImmutable::now(),
            completedAt: null,
            status: 'in_progress',
            createdAt: CarbonImmutable::now(),
            updatedAt: CarbonImmutable::now(),
        );
    }

    public function isComplete(): bool
    {
        return $this->status === 'completed' && $this->completedAt !== null;
    }

    public function isHighAggression(): bool
    {
        return $this->aggressionLevel >= 7;
    }

    public function isCriticalAggression(): bool
    {
        return $this->aggressionLevel >= 9;
    }

    public function isHighStress(): bool
    {
        return $this->stressLevelAfter >= 8;
    }

    public function hasSafetyIncident(): bool
    {
        return $this->safetyIncident;
    }

    public function requiresSecondGroomer(): bool
    {
        return $this->aggressionLevel >= 6;
    }

    public function requiresVeterinarian(): bool
    {
        return $this->aggressionLevel >= 8 || $this->hasSafetyIncident();
    }

    public function hasSecondGroomer(): bool
    {
        return $this->secondGroomerId !== null;
    }

    public function hasVeterinarian(): bool
    {
        return $this->veterinarianId !== null;
    }

    public function isGiantBreed(): bool
    {
        return $this->mammalGroup === 'giant_dog';
    }

    public function isLargeCat(): bool
    {
        return $this->mammalGroup === 'large_cat';
    }

    public function requiresMasterCertification(): bool
    {
        return $this->isGiantBreed() || $this->isCriticalAggression() || $this->procedureType === ExoticProcedureType::SHOW_GROOM;
    }

    public function requiresAdvancedCertification(): bool
    {
        return $this->isHighAggression() || $this->isLargeCat();
    }

    public function isCompliantWithSafetyRules(): bool
    {
        if ($this->requiresSecondGroomer() && !$this->hasSecondGroomer()) {
            return false;
        }

        if ($this->requiresVeterinarian() && !$this->hasVeterinarian()) {
            return false;
        }

        return true;
    }

    public function complete(
        int $stressLevelAfter,
        int $durationMinutes,
        ?array $protocolChecklist,
        ?array $afterPhotos,
        ?string $notes,
        ?array $medicalNotes,
        ?int $secondGroomerId = null,
        ?int $veterinarianId = null,
    ): self {
        return new self(
            id: $this->id,
            petId: $this->petId,
            groomerId: $this->groomerId,
            tenantId: $this->tenantId,
            mammalGroup: $this->mammalGroup,
            procedureType: $this->procedureType,
            aggressionLevel: $this->aggressionLevel,
            stressLevelBefore: $this->stressLevelBefore,
            stressLevelAfter: $stressLevelAfter,
            restraintMethod: $this->restraintMethod,
            safetyIncident: $this->safetyIncident,
            safetyIncidentDescription: $this->safetyIncidentDescription,
            secondGroomerId: $secondGroomerId ?? $this->secondGroomerId,
            veterinarianId: $veterinarianId ?? $this->veterinarianId,
            durationMinutes: $durationMinutes,
            protocolChecklist: $protocolChecklist,
            beforePhotos: $this->beforePhotos,
            afterPhotos: $afterPhotos,
            notes: $notes,
            medicalNotes: $medicalNotes,
            appointmentId: $this->appointmentId,
            startedAt: $this->startedAt,
            completedAt: CarbonImmutable::now(),
            status: 'completed',
            createdAt: $this->createdAt,
            updatedAt: CarbonImmutable::now(),
        );
    }

    public function recordSafetyIncident(string $description): self
    {
        return new self(
            id: $this->id,
            petId: $this->petId,
            groomerId: $this->groomerId,
            tenantId: $this->tenantId,
            mammalGroup: $this->mammalGroup,
            procedureType: $this->procedureType,
            aggressionLevel: $this->aggressionLevel,
            stressLevelBefore: $this->stressLevelBefore,
            stressLevelAfter: $this->stressLevelAfter,
            restraintMethod: $this->restraintMethod,
            safetyIncident: true,
            safetyIncidentDescription: $description,
            secondGroomerId: $this->secondGroomerId,
            veterinarianId: $this->veterinarianId,
            durationMinutes: $this->durationMinutes,
            protocolChecklist: $this->protocolChecklist,
            beforePhotos: $this->beforePhotos,
            afterPhotos: $this->afterPhotos,
            notes: $this->notes,
            medicalNotes: $this->medicalNotes,
            appointmentId: $this->appointmentId,
            startedAt: $this->startedAt,
            completedAt: $this->completedAt,
            status: $this->status,
            createdAt: $this->createdAt,
            updatedAt: CarbonImmutable::now(),
        );
    }

    public function assignSecondGroomer(int $groomerId): self
    {
        return new self(
            id: $this->id,
            petId: $this->petId,
            groomerId: $this->groomerId,
            tenantId: $this->tenantId,
            mammalGroup: $this->mammalGroup,
            procedureType: $this->procedureType,
            aggressionLevel: $this->aggressionLevel,
            stressLevelBefore: $this->stressLevelBefore,
            stressLevelAfter: $this->stressLevelAfter,
            restraintMethod: $this->restraintMethod,
            safetyIncident: $this->safetyIncident,
            safetyIncidentDescription: $this->safetyIncidentDescription,
            secondGroomerId: $groomerId,
            veterinarianId: $this->veterinarianId,
            durationMinutes: $this->durationMinutes,
            protocolChecklist: $this->protocolChecklist,
            beforePhotos: $this->beforePhotos,
            afterPhotos: $this->afterPhotos,
            notes: $this->notes,
            medicalNotes: $this->medicalNotes,
            appointmentId: $this->appointmentId,
            startedAt: $this->startedAt,
            completedAt: $this->completedAt,
            status: $this->status,
            createdAt: $this->createdAt,
            updatedAt: CarbonImmutable::now(),
        );
    }

    public function assignVeterinarian(int $vetId): self
    {
        return new self(
            id: $this->id,
            petId: $this->petId,
            groomerId: $this->groomerId,
            tenantId: $this->tenantId,
            mammalGroup: $this->mammalGroup,
            procedureType: $this->procedureType,
            aggressionLevel: $this->aggressionLevel,
            stressLevelBefore: $this->stressLevelBefore,
            stressLevelAfter: $this->stressLevelAfter,
            restraintMethod: $this->restraintMethod,
            safetyIncident: $this->safetyIncident,
            safetyIncidentDescription: $this->safetyIncidentDescription,
            secondGroomerId: $this->secondGroomerId,
            veterinarianId: $vetId,
            durationMinutes: $this->durationMinutes,
            protocolChecklist: $this->protocolChecklist,
            beforePhotos: $this->beforePhotos,
            afterPhotos: $this->afterPhotos,
            notes: $this->notes,
            medicalNotes: $this->medicalNotes,
            appointmentId: $this->appointmentId,
            startedAt: $this->startedAt,
            completedAt: $this->completedAt,
            status: $this->status,
            createdAt: $this->createdAt,
            updatedAt: CarbonImmutable::now(),
        );
    }

    public function updateProtocolChecklist(array $checklist): self
    {
        return new self(
            id: $this->id,
            petId: $this->petId,
            groomerId: $this->groomerId,
            tenantId: $this->tenantId,
            mammalGroup: $this->mammalGroup,
            procedureType: $this->procedureType,
            aggressionLevel: $this->aggressionLevel,
            stressLevelBefore: $this->stressLevelBefore,
            stressLevelAfter: $this->stressLevelAfter,
            restraintMethod: $this->restraintMethod,
            safetyIncident: $this->safetyIncident,
            safetyIncidentDescription: $this->safetyIncidentDescription,
            secondGroomerId: $this->secondGroomerId,
            veterinarianId: $this->veterinarianId,
            durationMinutes: $this->durationMinutes,
            protocolChecklist: $checklist,
            beforePhotos: $this->beforePhotos,
            afterPhotos: $this->afterPhotos,
            notes: $this->notes,
            medicalNotes: $this->medicalNotes,
            appointmentId: $this->appointmentId,
            startedAt: $this->startedAt,
            completedAt: $this->completedAt,
            status: $this->status,
            createdAt: $this->createdAt,
            updatedAt: CarbonImmutable::now(),
        );
    }

    public function addBeforePhotos(array $photos): self
    {
        return new self(
            id: $this->id,
            petId: $this->petId,
            groomerId: $this->groomerId,
            tenantId: $this->tenantId,
            mammalGroup: $this->mammalGroup,
            procedureType: $this->procedureType,
            aggressionLevel: $this->aggressionLevel,
            stressLevelBefore: $this->stressLevelBefore,
            stressLevelAfter: $this->stressLevelAfter,
            restraintMethod: $this->restraintMethod,
            safetyIncident: $this->safetyIncident,
            safetyIncidentDescription: $this->safetyIncidentDescription,
            secondGroomerId: $this->secondGroomerId,
            veterinarianId: $this->veterinarianId,
            durationMinutes: $this->durationMinutes,
            protocolChecklist: $this->protocolChecklist,
            beforePhotos: $photos,
            afterPhotos: $this->afterPhotos,
            notes: $this->notes,
            medicalNotes: $this->medicalNotes,
            appointmentId: $this->appointmentId,
            startedAt: $this->startedAt,
            completedAt: $this->completedAt,
            status: $this->status,
            createdAt: $this->createdAt,
            updatedAt: CarbonImmutable::now(),
        );
    }
}
