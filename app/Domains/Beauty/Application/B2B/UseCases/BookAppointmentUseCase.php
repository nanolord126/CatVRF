<?php

declare(strict_types=1);

namespace App\Domains\Beauty\Application\B2B\UseCases;


use Psr\Log\LoggerInterface;
use App\Domains\Beauty\Application\B2B\DTOs\BookAppointmentDTO;
use App\Domains\Beauty\Domain\Entities\Appointment;
use App\Domains\Beauty\Domain\Enums\AppointmentStatus;
use App\Domains\Beauty\Domain\Events\AppointmentBooked;
use App\Domains\Beauty\Domain\Repositories\AppointmentRepositoryInterface;
use App\Domains\Beauty\Domain\Repositories\MasterRepositoryInterface;
use App\Domains\Beauty\Domain\Repositories\SalonRepositoryInterface;
use App\Domains\Beauty\Domain\Repositories\ServiceRepositoryInterface;
use App\Domains\Beauty\Domain\Services\ConsumableDeductionServiceInterface;
use App\Domains\Beauty\Domain\ValueObjects\MasterId;
use App\Domains\Beauty\Domain\ValueObjects\SalonId;
use App\Domains\Beauty\Domain\ValueObjects\ServiceId;
use App\Services\FraudControlService;
use App\Shared\Domain\ValueObjects\ClientId;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Events\Dispatcher as EventDispatcher;

/**
 * B2B: Создать запись от имени администратора/сотрудника салона.
 */
final readonly class BookAppointmentUseCase
{
    public function __construct(
        private AppointmentRepositoryInterface $appointmentRepository,
        private SalonRepositoryInterface $salonRepository,
        private MasterRepositoryInterface $masterRepository,
        private ServiceRepositoryInterface $serviceRepository,
        private ConsumableDeductionServiceInterface $consumableDeduction,
        private FraudControlService $fraud,
        private \Illuminate\Database\DatabaseManager $db,
        private LoggerInterface $logger,
        private EventDispatcher $events,
    ) {
    }

    public function handle(BookAppointmentDTO $dto): Appointment
    {
        $fraud = $this->fraud->check(
            userId: $dto->clientId,
            operationType: 'beauty.appointment.book',
            amount: 0,
            correlationId: $dto->correlationId,
        );

        if ($fraud['decision'] === 'block') {
            throw new \DomainException('Operation blocked by fraud control. Correlation: ' . $dto->correlationId);
        }

        $salonId   = SalonId::fromString($dto->salonUuid);
        $masterId  = MasterId::fromString($dto->masterUuid);
        $serviceId = ServiceId::fromString($dto->serviceUuid);

        $salon   = $this->salonRepository->findById($salonId);
        $master  = $this->masterRepository->findById($masterId);
        $service = $this->serviceRepository->findById($serviceId);

        if ($salon === null) {
            throw new \DomainException("Salon [{$dto->salonUuid}] not found.");
        }
        if ($master === null) {
            throw new \DomainException("Master [{$dto->masterUuid}] not found.");
        }
        if ($service === null || !$service->isActive()) {
            throw new \DomainException("Service [{$dto->serviceUuid}] not found or inactive.");
        }

        if (!$this->consumableDeduction->hasEnoughStock($serviceId)) {
            throw new \DomainException("Insufficient consumable stock for service [{$dto->serviceUuid}].");
        }

        $startAt = CarbonImmutable::parse($dto->startAt);
        $endAt   = $startAt->addMinutes($service->getDuration()->getMinutes());

        return $this->db->transaction(function () use ($dto, $salonId, $masterId, $serviceId, $service, $startAt, $endAt): Appointment {
            $appointmentId = $this->appointmentRepository->nextIdentity();

            $appointment = new Appointment(
                id: $appointmentId,
                salonId: $salonId,
                masterId: $masterId,
                serviceId: $serviceId,
                clientId: new ClientId($dto->clientId),
                startAt: $startAt,
                endAt: $endAt,
                price: $service->getPrice(),
                status: AppointmentStatus::PENDING,
                createdAt: new \DateTimeImmutable(),
                updatedAt: new \DateTimeImmutable(),
            );

            $this->appointmentRepository->save($appointment);

            $this->consumableDeduction->holdConsumables(
                appointmentId: $appointmentId,
                serviceId: $serviceId,
                correlationId: $dto->correlationId,
            );

            $this->events->dispatch(new AppointmentBooked(
                appointmentId: $appointmentId,
                clientId: new ClientId($dto->clientId),
                startAt: $startAt,
                correlationId: $dto->correlationId,
            ));

            $this->logger->info('B2B: Appointment booked', [
                'correlation_id'  => $dto->correlationId,
                'appointment_id'  => $appointmentId->getValue(),
                'tenant_id'       => $dto->tenantId,
                'client_id'       => $dto->clientId,
                'service_id'      => $dto->serviceUuid,
            ]);

            return $appointment;
        });
    }
}
