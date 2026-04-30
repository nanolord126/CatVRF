<?php

declare(strict_types=1);

namespace App\Domains\Shared\Medical\Domain\Events;

use App\Shared\Domain\Events\DomainEvent;
use Ramsey\Uuid\Uuid;

/**
 * Domain Event: Emergency medical situation detected
 * Critical event requiring immediate processing
 */
final class EmergencyDetectedDomainEvent extends DomainEvent
{
    public function __construct(
        private readonly string $emergencyId,
        private readonly string $patientId,
        private readonly string $emergencyType,
        private readonly int $severityLevel, // 1-10, 10 being most critical
        private readonly ?string $location = null,
        private readonly ?string $symptoms = null,
        mixed $correlationId = null,
    ) {
        parent::__construct($correlationId ?? Uuid::uuid4()->toString());
    }

    public function getEmergencyId(): string
    {
        return $this->emergencyId;
    }

    public function getPatientId(): string
    {
        return $this->patientId;
    }

    public function getEmergencyType(): string
    {
        return $this->emergencyType;
    }

    public function getSeverityLevel(): int
    {
        return $this->severityLevel;
    }

    public function getLocation(): ?string
    {
        return $this->location;
    }

    public function getSymptoms(): ?string
    {
        return $this->symptoms;
    }

    public function eventName(): string
    {
        return 'medical.emergency.detected';
    }

    public function toArray(): array
    {
        return [
            'emergency_id' => $this->emergencyId,
            'patient_id' => $this->patientId,
            'emergency_type' => $this->emergencyType,
            'severity_level' => $this->severityLevel,
            'location' => $this->location,
            'symptoms' => $this->symptoms,
            'correlation_id' => $this->getCorrelationId(),
        ];
    }
}
