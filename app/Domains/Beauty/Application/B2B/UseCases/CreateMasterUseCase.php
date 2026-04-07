<?php

declare(strict_types=1);

namespace App\Domains\Beauty\Application\B2B\UseCases;


use Psr\Log\LoggerInterface;
use App\Domains\Beauty\Application\B2B\DTOs\CreateMasterDTO;
use App\Domains\Beauty\Domain\Entities\Master;
use App\Domains\Beauty\Domain\Events\MasterAdded;
use App\Domains\Beauty\Domain\Repositories\MasterRepositoryInterface;
use App\Domains\Beauty\Domain\Repositories\SalonRepositoryInterface;
use App\Domains\Beauty\Domain\ValueObjects\SalonId;
use App\Domains\Beauty\Domain\ValueObjects\Schedule;
use App\Services\FraudControlService;
use Illuminate\Contracts\Events\Dispatcher as EventDispatcher;
use Illuminate\Support\Collection;

/**
 * B2B: Зарегистрировать нового мастера в салоне.
 */
final readonly class CreateMasterUseCase
{
    public function __construct(
        private MasterRepositoryInterface $masterRepository,
        private SalonRepositoryInterface $salonRepository,
        private FraudControlService $fraud,
        private \Illuminate\Database\DatabaseManager $db,
        private LoggerInterface $logger,
        private EventDispatcher $events,
    ) {
    }

    public function handle(CreateMasterDTO $dto): Master
    {
        $fraud = $this->fraud->check(
            userId: 0,
            operationType: 'beauty.master.create',
            amount: 0,
            correlationId: $dto->correlationId,
        );

        if ($fraud['decision'] === 'block') {
            throw new \DomainException('Operation blocked by fraud control. Correlation: ' . $dto->correlationId);
        }

        $salonId = SalonId::fromString($dto->salonUuid);
        $salon   = $this->salonRepository->findById($salonId);

        if ($salon === null) {
            throw new \DomainException("Salon [{$dto->salonUuid}] not found.");
        }

        return $this->db->transaction(function () use ($dto, $salonId): Master {
            $masterId = $this->masterRepository->nextIdentity();

            $weeklySchedule = [];
            foreach ($dto->workDays as $day) {
                $weeklySchedule[(int) $day] = [
                    'start' => $dto->workStart,
                    'end'   => $dto->workEnd,
                ];
            }

            $master = new Master(
                id: $masterId,
                salonId: $salonId,
                name: $dto->name,
                specialization: $dto->specialization,
                experienceYears: $dto->experienceYears,
                schedule: new Schedule($weeklySchedule),
                photo: null,
                services: new Collection(),
                portfolio: new Collection(),
                rating: 0.0,
                reviewCount: 0,
                createdAt: new \DateTimeImmutable(),
                updatedAt: new \DateTimeImmutable(),
            );

            $this->masterRepository->save($master);

            $this->events->dispatch(new MasterAdded(
                masterId: $masterId,
                salonId: $salonId,
                correlationId: $dto->correlationId,
            ));

            $this->logger->info('B2B: Master created', [
                'correlation_id' => $dto->correlationId,
                'master_id'      => $masterId->getValue(),
                'salon_id'       => $dto->salonUuid,
                'tenant_id'      => $dto->tenantId,
            ]);

            return $master;
        });
    }
}
