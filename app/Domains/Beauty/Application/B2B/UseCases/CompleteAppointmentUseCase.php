<?php

declare(strict_types=1);

namespace App\Domains\Beauty\Application\B2B\UseCases;


use Psr\Log\LoggerInterface;
use App\Domains\Beauty\Application\B2B\DTOs\CompleteAppointmentDTO;
use App\Domains\Beauty\Domain\Entities\Appointment;
use App\Domains\Beauty\Domain\Events\AppointmentCompleted;
use App\Domains\Beauty\Domain\Repositories\AppointmentRepositoryInterface;
use App\Domains\Beauty\Domain\ValueObjects\AppointmentId;
use App\Services\FraudControlService;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Events\Dispatcher as EventDispatcher;

/**
 * B2B: Завершить услугу (CONFIRMED → COMPLETED).
 * После завершения диспатчится AppointmentCompleted → DeductAppointmentConsumablesListener.
 */
final readonly class CompleteAppointmentUseCase
{
    public function __construct(
        private AppointmentRepositoryInterface $appointmentRepository,
        private FraudControlService $fraud,
        private \Illuminate\Database\DatabaseManager $db,
        private LoggerInterface $logger,
        private EventDispatcher $events,
    ) {
    }

    public function handle(CompleteAppointmentDTO $dto): Appointment
    {
        $fraud = $this->fraud->check(
            userId: $dto->completedByUserId,
            operationType: 'beauty.appointment.complete',
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
            $appointment->complete();
            $this->appointmentRepository->save($appointment);

            $this->events->dispatch(new AppointmentCompleted(
                appointmentId: $appointment->getId(),
                salonId:       $appointment->getSalonId(),
                masterId:      $appointment->getMasterId(),
                serviceId:     $appointment->getServiceId(),
                clientId:      $appointment->getClientId(),
                completedAt:   CarbonImmutable::now(),
                correlationId: $dto->correlationId,
            ));

            $this->logger->info('B2B: Appointment completed', [
                'correlation_id'       => $dto->correlationId,
                'appointment_id'       => $dto->appointmentUuid,
                'tenant_id'            => $dto->tenantId,
                'completed_by_user_id' => $dto->completedByUserId,
            ]);

            return $appointment;
        });
    }
}
