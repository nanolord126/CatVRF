<?php

declare(strict_types=1);

namespace Modules\VetGrooming\Domain\Entities;

use Modules\VetGrooming\Domain\Enums\ExoticCategory;
use Modules\VetGrooming\Domain\Enums\ExoticProcedureType;
use Modules\VetGrooming\Domain\Enums\HandlingMethod;
use Carbon\CarbonImmutable;

final readonly class ExoticGroomingSession
{
    public function __construct(
        public int $id,
        public int $petId,
        public int $groomerId,
        public int $tenantId,
        public ExoticCategory $exoticType,
        public string $speciesGroup,
        public ExoticProcedureType $procedureType,
        public int $stressLevelBefore,
        public int $stressLevelAfter,
        public int $durationMinutes,
        public bool $temperatureControlled,
        public ?float $roomTemperature,
        public ?bool $sedationUsed,
        public ?string $sedationNotes,
        public HandlingMethod $handlingMethod,
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
        ExoticCategory $exoticType,
        string $speciesGroup,
        ExoticProcedureType $procedureType,
        int $stressLevelBefore,
        HandlingMethod $handlingMethod,
        ?int $appointmentId = null,
    ): self {
        return new self(
            id: 0,
            petId: $petId,
            groomerId: $groomerId,
            tenantId: $tenantId,
            exoticType: $exoticType,
            speciesGroup: $speciesGroup,
            procedureType: $procedureType,
            stressLevelBefore: $stressLevelBefore,
            stressLevelAfter: 0,
            durationMinutes: 0,
            temperatureControlled: false,
            roomTemperature: null,
            sedationUsed: null,
            sedationNotes: null,
            handlingMethod: $handlingMethod,
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

    public function isInProgress(): bool
    {
        return $this->status === 'in_progress';
    }

    public function isHighStress(): bool
    {
        return $this->stressLevelAfter >= 7;
    }

    public function isCriticalStress(): bool
    {
        return $this->stressLevelAfter >= 9;
    }

    public function hasSedation(): bool
    {
        return $this->sedationUsed === true;
    }

    public function isTemperatureControlled(): bool
    {
        return $this->temperatureControlled && $this->roomTemperature !== null;
    }

    public function requiresVeterinaryNotification(): bool
    {
        return $this->isHighStress() || $this->hasSedation();
    }

    public function complete(
        int $stressLevelAfter,
        int $durationMinutes,
        bool $temperatureControlled,
        ?float $roomTemperature,
        ?array $protocolChecklist,
        ?array $afterPhotos,
        ?string $notes,
        ?array $medicalNotes,
    ): self {
        return new self(
            id: $this->id,
            petId: $this->petId,
            groomerId: $this->groomerId,
            tenantId: $this->tenantId,
            exoticType: $this->exoticType,
            speciesGroup: $this->speciesGroup,
            procedureType: $this->procedureType,
            stressLevelBefore: $this->stressLevelBefore,
            stressLevelAfter: $stressLevelAfter,
            durationMinutes: $durationMinutes,
            temperatureControlled: $temperatureControlled,
            roomTemperature: $roomTemperature,
            sedationUsed: $this->sedationUsed,
            sedationNotes: $this->sedationNotes,
            handlingMethod: $this->handlingMethod,
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

    public function addBeforePhotos(array $photos): self
    {
        return new self(
            id: $this->id,
            petId: $this->petId,
            groomerId: $this->groomerId,
            tenantId: $this->tenantId,
            exoticType: $this->exoticType,
            speciesGroup: $this->speciesGroup,
            procedureType: $this->procedureType,
            stressLevelBefore: $this->stressLevelBefore,
            stressLevelAfter: $this->stressLevelAfter,
            durationMinutes: $this->durationMinutes,
            temperatureControlled: $this->temperatureControlled,
            roomTemperature: $this->roomTemperature,
            sedationUsed: $this->sedationUsed,
            sedationNotes: $this->sedationNotes,
            handlingMethod: $this->handlingMethod,
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

    public function updateProtocolChecklist(array $checklist): self
    {
        return new self(
            id: $this->id,
            petId: $this->petId,
            groomerId: $this->groomerId,
            tenantId: $this->tenantId,
            exoticType: $this->exoticType,
            speciesGroup: $this->speciesGroup,
            procedureType: $this->procedureType,
            stressLevelBefore: $this->stressLevelBefore,
            stressLevelAfter: $this->stressLevelAfter,
            durationMinutes: $this->durationMinutes,
            temperatureControlled: $this->temperatureControlled,
            roomTemperature: $this->roomTemperature,
            sedationUsed: $this->sedationUsed,
            sedationNotes: $this->sedationNotes,
            handlingMethod: $this->handlingMethod,
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

    public function recordSedation(string $notes): self
    {
        return new self(
            id: $this->id,
            petId: $this->petId,
            groomerId: $this->groomerId,
            tenantId: $this->tenantId,
            exoticType: $this->exoticType,
            speciesGroup: $this->speciesGroup,
            procedureType: $this->procedureType,
            stressLevelBefore: $this->stressLevelBefore,
            stressLevelAfter: $this->stressLevelAfter,
            durationMinutes: $this->durationMinutes,
            temperatureControlled: $this->temperatureControlled,
            roomTemperature: $this->roomTemperature,
            sedationUsed: true,
            sedationNotes: $notes,
            handlingMethod: $this->handlingMethod,
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
}
