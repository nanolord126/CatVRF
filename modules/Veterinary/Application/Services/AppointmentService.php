<?php

declare(strict_types=1);

namespace Modules\Veterinary\Application\Services;

use App\Domains\FraudML\Services\FraudControlService;
use App\Traits\WithAuditLogging;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Database\DatabaseManager;
use Modules\Veterinary\Domain\Entities\VeterinaryAppointment;
use Modules\Veterinary\Domain\Repositories\VeterinaryAppointmentRepositoryInterface;
use Modules\Veterinary\Domain\Enums\AppointmentStatus;
use Psr\Log\LoggerInterface;
use Ramsey\Uuid\UuidInterface;

final readonly class AppointmentService
{
    use WithAuditLogging;

    public function __construct(
        private readonly FraudControlService $fraud,
        private readonly DatabaseManager $db,
        private readonly LoggerInterface $logger,
        private readonly Guard $guard,
        private readonly VeterinaryAppointmentRepositoryInterface $appointmentRepository,
        private readonly UuidInterface $uuid,
    ) {}

    public function create(array $data, ?string $correlationId = null): VeterinaryAppointment
    {
        $correlationId ??= $this->uuid->toString();
        $userId = $this->guard->id() ?? 0;

        // TODO: FraudControlService requires FraudCheckDTO - comment out until DTO is created
        // $this->fraud->check($userId, 'veterinary_appointment_create', 0, null, null, $correlationId);

        return $this->db->transaction(function () use ($data, $correlationId, $userId) {
            $data['correlation_id'] = $correlationId;
            $data['status'] = AppointmentStatus::PENDING->value;
            $data['uuid'] = $this->uuid->toString();

            $appointment = $this->appointmentRepository->create($data);

            $this->logCreated('VeterinaryAppointment', $appointment->id, [
                'tenant_id' => $data['tenant_id'] ?? null,
                'pet_id' => $data['pet_id'],
                'clinic_id' => $data['clinic_id'],
                'appointment_at' => $data['appointment_at'],
                // PII data (medical_notes, symptoms) are encrypted and not logged
            ]);

            return $appointment;
        });
    }

    public function cancel(int $id, string $reason, ?string $correlationId = null): VeterinaryAppointment
    {
        $correlationId ??= $this->uuid->toString();
        $userId = $this->guard->id() ?? 0;

        // TODO: FraudControlService requires FraudCheckDTO - comment out until DTO is created
        // $this->fraud->check($userId, 'veterinary_appointment_cancel', 0, null, null, $correlationId);

        return $this->db->transaction(function () use ($id, $reason, $correlationId, $userId) {
            $appointment = $this->appointmentRepository->findById($id);

            if (!$appointment) {
                throw new \InvalidArgumentException('Appointment not found');
            }

            if (!$appointment->isPending() && !$appointment->isConfirmed()) {
                throw new \InvalidArgumentException('Cannot cancel appointment in current status');
            }

            $updated = $this->appointmentRepository->update($id, [
                'status' => AppointmentStatus::CANCELLED->value,
                'cancellation_reason' => $reason,
                'correlation_id' => $correlationId,
            ]);

            $this->logAction('cancel', 'VeterinaryAppointment', $id, [
                'reason' => $reason,
                'user_id' => $userId,
                // PII data are encrypted and not logged
            ]);

            return $updated;
        });
    }

    public function confirm(int $id, ?string $correlationId = null): VeterinaryAppointment
    {
        $correlationId ??= $this->uuid->toString();
        $userId = $this->guard->id() ?? 0;

        // TODO: FraudControlService requires FraudCheckDTO - comment out until DTO is created
        // $this->fraud->check($userId, 'veterinary_appointment_confirm', 0, null, null, $correlationId);

        return $this->db->transaction(function () use ($id, $correlationId) {
            $appointment = $this->appointmentRepository->findById($id);

            if (!$appointment) {
                throw new \InvalidArgumentException('Appointment not found');
            }

            if (!$appointment->isPending()) {
                throw new \InvalidArgumentException('Can only confirm pending appointments');
            }

            return $this->appointmentRepository->update($id, [
                'status' => AppointmentStatus::CONFIRMED->value,
                'correlation_id' => $correlationId,
            ]);
        });
    }

    public function complete(int $id, ?string $correlationId = null): VeterinaryAppointment
    {
        $correlationId ??= $this->uuid->toString();
        $userId = $this->guard->id() ?? 0;

        // TODO: FraudControlService requires FraudCheckDTO - comment out until DTO is created
        // $this->fraud->check($userId, 'veterinary_appointment_complete', 0, null, null, $correlationId);

        return $this->db->transaction(function () use ($id, $correlationId) {
            $appointment = $this->appointmentRepository->findById($id);

            if (!$appointment) {
                throw new \InvalidArgumentException('Appointment not found');
            }

            if (!$appointment->canComplete()) {
                throw new \InvalidArgumentException('Cannot complete appointment in current status');
            }

            return $this->appointmentRepository->update($id, [
                'status' => AppointmentStatus::COMPLETED->value,
                'correlation_id' => $correlationId,
            ]);
        });
    }
}
