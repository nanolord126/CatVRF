<?php

declare(strict_types=1);

namespace App\Domains\Auto\Taxi\Application\B2B\UseCases;



use Illuminate\Contracts\Auth\Guard;
use Psr\Log\LoggerInterface;
use App\Domains\Auto\Taxi\Domain\Enums\RideStatusEnum;
use App\Domains\Auto\Taxi\Domain\Events\RideStarted;
use App\Domains\Auto\Taxi\Domain\Repository\RideRepositoryInterface;
use App\Domains\Auto\Taxi\Domain\ValueObjects\DriverId;
use App\Domains\Auto\Taxi\Domain\ValueObjects\RideId;
use App\Services\FraudControlService;
use Illuminate\Contracts\Events\Dispatcher;
use Throwable;

final class StartRideUseCase
{
    public function __construct(private readonly RideRepositoryInterface $rideRepository,
        private readonly FraudControlService $fraud,
        private readonly Dispatcher $dispatcher,
        private readonly \Illuminate\Database\DatabaseManager $db, private readonly LoggerInterface $logger, private readonly Guard $guard) {

    }

    /** @throws Throwable */
    public function __invoke(RideId $rideId, DriverId $driverId, string $correlationId): void
    {
        $this->logger->info('StartRideUseCase started', [
            'correlation_id' => $correlationId,
            'ride_id' => $rideId->toString(),
        ]);

        $this->fraud->check(userId: $this->guard->id() ?? 0, operationType: 'mutation', amount: 0, correlationId: $correlationId ?? '');

        $this->db->transaction(function () use ($rideId, $driverId, $correlationId) {
            $ride = $this->rideRepository->findById($rideId);

            if ($ride === null) {
                throw new \DomainException("Ride {$rideId->toString()} not found.");
            }

            if ($ride->getStatus() !== RideStatusEnum::Accepted) {
                throw new \DomainException("Ride {$rideId->toString()} is not in 'accepted' status.");
            }

            if ($ride->getDriverId()?->toString() !== $driverId->toString()) {
                throw new \DomainException("Driver {$driverId->toString()} is not assigned to this ride.");
            }

            $ride->start();
            $this->rideRepository->save($ride);

            $this->dispatcher->dispatch(new RideStarted(
                rideId: $rideId->toString(),
                driverId: $driverId->toString(),
                correlationId: $correlationId,
            ));

            $this->logger->info('Ride started', [
                'correlation_id' => $correlationId,
                'ride_id' => $rideId->toString(),
            ]);
        });
    }
}
