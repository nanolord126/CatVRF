<?php

declare(strict_types=1);

namespace App\Domains\Beauty\Application\B2B\UseCases;


use Psr\Log\LoggerInterface;
use App\Domains\Beauty\Application\B2B\DTOs\ConfirmAppointmentDTO;
use App\Domains\Beauty\Domain\Entities\Appointment;
use App\Domains\Beauty\Domain\Repositories\AppointmentRepositoryInterface;
use App\Domains\Beauty\Domain\ValueObjects\AppointmentId;
use App\Services\FraudControlService;

/**
 * B2B: Подтвердить запись (PENDING → CONFIRMED).
 * Вызывается администратором или автоматически при онлайн-бронировании.
 */
final readonly class ConfirmAppointmentUseCase
{
    public function __construct(
        private AppointmentRepositoryInterface $appointmentRepository,
        private FraudControlService $fraud,
        private \Illuminate\Database\DatabaseManager $db,
        private LoggerInterface $logger,
    ) {
    }

    public function handle(ConfirmAppointmentDTO $dto): Appointment
    {
        $fraud = $this->fraud->check(
            userId: $dto->confirmedByUserId,
            operationType: 'beauty.appointment.confirm',
            amount: 0,
            correlationId: $dto->correlationId,
        );

        if ($fraud['decision'] === 'block') {
            throw new \DomainException('Operation blocked by fraud control. Correlation: ' . $dto->correlationId);
        }

        $appointmentId = AppointmentId::fromString($dto->appointmentUuid);
        $appointment   = $this->appointmentRepository->findById($appointmentId);

        if ($appointment === null) {
            throw new \DomainException("Appointment [{$dto->appointmentUuid}] not found.");
        }

        return $this->db->transaction(function () use ($dto, $appointment): Appointment {
            $appointment->confirm();
            $this->appointmentRepository->save($appointment);

            $this->logger->info('B2B: Appointment confirmed', [
                'correlation_id'      => $dto->correlationId,
                'appointment_id'      => $dto->appointmentUuid,
                'tenant_id'           => $dto->tenantId,
                'confirmed_by_user_id' => $dto->confirmedByUserId,
            ]);

            return $appointment;
        });
    }
}
