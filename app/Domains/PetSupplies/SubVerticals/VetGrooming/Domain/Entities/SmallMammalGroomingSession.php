<?php

declare(strict_types=1);

namespace Modules\VetGrooming\Domain\Entities;

use Modules\VetGrooming\Domain\Enums\ExoticProcedureType;
use Modules\VetGrooming\Domain\Enums\HandlingMethod;
use Carbon\CarbonImmutable;

final readonly class SmallMammalGroomingSession
{
    public function __construct(
        public int $id,
        public int $petId,
        public int $groomerId,
        public int $tenantId,
        public string $mammalGroup,
        public ExoticProcedureType $procedureType,
        public int $stressLevelBefore,
        public int $stressLevelAfter,
        public HandlingMethod $handlingMethod,
        public bool $sedationUsed,
        public ?string $sedationNotes,
        public bool $temperatureControlled,
        public ?float $roomTemperature,
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
        int $stressLevelBefore,
        HandlingMethod $handlingMethod,
        ?int $appointmentId = null,
    ): self {
        return new self(
            id: 0,
            petId: $petId,
            groomerId: $groomerId,
            tenantId: $tenantId,
            mammalGroup: $mammalGroup,
            procedureType: $procedureType,
            stressLevelBefore: $stressLevelBefore,
            stressLevelAfter: 0,
            handlingMethod: $handlingMethod,
            sedationUsed: false,
            sedationNotes: null,
            temperatureControlled: false,
            roomTemperature: null,
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

    public function isHighStress(): bool
    {
        return $this->stressLevelAfter >= 7;
    }

    public function isCriticalStress(): bool
    {
        return $this->stressLevelAfter >= 9;
    }

    public function isFerret(): bool
    {
        return $this->mammalGroup === 'ferret';
    }

    public function isRabbit(): bool
    {
        return $this->mammalGroup === 'rabbit';
    }

    public function isChinchilla(): bool
    {
        return $this->mammalGroup === 'chinchilla';
    }

    public function isHedgehog(): bool
    {
        return $this->mammalGroup === 'hedgehog';
    }

    public function requiresVeterinaryNotification(): bool
    {
        return $this->isHighStress() || $this->sedationUsed;
    }

    public function requiresAdvancedCertification(): bool
    {
        return in_array($this->mammalGroup, ['ferret', 'rabbit', 'chinchilla']);
    }

    public function requiresMasterCertification(): bool
    {
        return $this->isFerret() && $this->procedureType === ExoticProcedureType::MAT_REMOVAL;
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
            mammalGroup: $this->mammalGroup,
            procedureType: $this->procedureType,
            stressLevelBefore: $this->stressLevelBefore,
            stressLevelAfter: $stressLevelAfter,
            handlingMethod: $this->handlingMethod,
            sedationUsed: $this->sedationUsed,
            sedationNotes: $this->sedationNotes,
            temperatureControlled: $temperatureControlled,
            roomTemperature: $roomTemperature,
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

    public function updateProtocolChecklist(array $checklist): self
    {
        return new self(
            id: $this->id,
            petId: $this->petId,
            groomerId: $this->groomerId,
            tenantId: $this->tenantId,
            mammalGroup: $this->mammalGroup,
            procedureType: $this->procedureType,
            stressLevelBefore: $this->stressLevelBefore,
            stressLevelAfter: $this->stressLevelAfter,
            handlingMethod: $this->handlingMethod,
            sedationUsed: $this->sedationUsed,
            sedationNotes: $this->sedationNotes,
            temperatureControlled: $this->temperatureControlled,
            roomTemperature: $this->roomTemperature,
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
            stressLevelBefore: $this->stressLevelBefore,
            stressLevelAfter: $this->stressLevelAfter,
            handlingMethod: $this->handlingMethod,
            sedationUsed: $this->sedationUsed,
            sedationNotes: $this->sedationNotes,
            temperatureControlled: $this->temperatureControlled,
            roomTemperature: $this->roomTemperature,
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
