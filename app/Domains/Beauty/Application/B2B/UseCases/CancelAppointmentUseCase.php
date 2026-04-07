<?php

declare(strict_types=1);

namespace App\Domains\Beauty\Application\B2B\UseCases;


use Psr\Log\LoggerInterface;
use App\Domains\Beauty\Application\B2B\DTOs\CancelAppointmentDTO;
use App\Domains\Beauty\Domain\Entities\Appointment;
use App\Domains\Beauty\Domain\Events\AppointmentCancelled;
use App\Domains\Beauty\Domain\Repositories\AppointmentRepositoryInterface;
use App\Domains\Beauty\Domain\Services\ConsumableDeductionServiceInterface;
use App\Domains\Beauty\Domain\ValueObjects\AppointmentId;
use App\Services\FraudControlService;
use App\Shared\Domain\ValueObjects\ClientId;
use Illuminate\Contracts\Events\Dispatcher as EventDispatcher;

/**
 * B2B: Отменить запись (любой статус → CANCELLED).
 * Снимает hold расходников. Диспатчит AppointmentCancelled.
 */
final readonly class CancelAppointmentUseCase
{
    public function __construct(
        private AppointmentRepositoryInterface $appointmentRepository,
        private ConsumableDeductionServiceInterface $consumableDeduction,
        private FraudControlService $fraud,
        private \Illuminate\Database\DatabaseManager $db,
        private LoggerInterface $logger,
        private EventDispatcher $events,
    ) {
    }

    public function handle(CancelAppointmentDTO $dto): Appointment
    {
        $fraud = $this->fraud->check(
            userId: $dto->cancelledByUserId,
            operationType: 'beauty.appointment.cancel',
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
            $appointment->cancel();
            $this->appointmentRepository->save($appointment);

            $this->consumableDeduction->releaseHold(
                appointmentId: $appointment->getId(),
                serviceId:     $appointment->getServiceId(),
                correlationId: $dto->correlationId,
            );

            $this->events->dispatch(new AppointmentCancelled(
                appointmentId: $appointment->getId(),
                clientId:      $appointment->getClientId(),
                reason:        $dto->reason,
                correlationId: $dto->correlationId,
            ));

            $this->logger->info('B2B: Appointment cancelled', [
                'correlation_id'        => $dto->correlationId,
                'appointment_id'        => $dto->appointmentUuid,
                'tenant_id'             => $dto->tenantId,
                'cancelled_by_user_id'  => $dto->cancelledByUserId,
                'reason'                => $dto->reason,
            ]);

            return $appointment;
        });
    }
}
