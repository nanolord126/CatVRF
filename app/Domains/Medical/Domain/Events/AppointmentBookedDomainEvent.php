<?php

declare(strict_types=1);

namespace App\Domains\Medical\Domain\Events;

use App\Shared\Domain\Events\DomainEvent;
use Ramsey\Uuid\Uuid;

/**
 * Domain Event: Appointment was booked
 * Pure fact, immutable, contains only business-relevant data
 */
final class AppointmentBookedDomainEvent extends DomainEvent
{
    public function __construct(
        private readonly string $appointmentId,
        private readonly string $doctorId,
        private readonly string $patientId,
        private readonly string $clinicId,
        private readonly \DateTimeImmutable $scheduledAt,
        private readonly string $appointmentType,
        mixed $correlationId = null,
    ) {
        parent::__construct($correlationId ?? Uuid::uuid4()->toString());
    }

    public function getAppointmentId(): string
    {
        return $this->appointmentId;
    }

    public function getDoctorId(): string
    {
        return $this->doctorId;
    }

    public function getPatientId(): string
    {
        return $this->patientId;
    }

    public function getClinicId(): string
    {
        return $this->clinicId;
    }

    public function getScheduledAt(): \DateTimeImmutable
    {
        return $this->scheduledAt;
    }

    public function getAppointmentType(): string
    {
        return $this->appointmentType;
    }

    public function eventName(): string
    {
        return 'medical.appointment.booked';
    }

    public function toArray(): array
    {
        return [
            'appointment_id' => $this->appointmentId,
            'doctor_id' => $this->doctorId,
            'patient_id' => $this->patientId,
            'clinic_id' => $this->clinicId,
            'scheduled_at' => $this->scheduledAt->format(DATE_ATOM),
            'appointment_type' => $this->appointmentType,
            'correlation_id' => $this->getCorrelationId(),
        ];
    }
}
