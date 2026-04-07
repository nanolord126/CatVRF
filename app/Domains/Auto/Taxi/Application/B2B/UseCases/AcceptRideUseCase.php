<?php

declare(strict_types=1);

namespace App\Domains\Auto\Taxi\Application\B2B\UseCases;



use Illuminate\Contracts\Auth\Guard;
use Psr\Log\LoggerInterface;
use App\Domains\Auto\Taxi\Domain\Enums\RideStatusEnum;
use App\Domains\Auto\Taxi\Domain\Events\RideAccepted;
use App\Domains\Auto\Taxi\Domain\Repository\DriverRepositoryInterface;
use App\Domains\Auto\Taxi\Domain\Repository\RideRepositoryInterface;
use App\Domains\Auto\Taxi\Domain\ValueObjects\DriverId;
use App\Domains\Auto\Taxi\Domain\ValueObjects\RideId;
use App\Services\FraudControlService;
use Illuminate\Contracts\Events\Dispatcher;
use Throwable;

final class AcceptRideUseCase
{
    public function __construct(private readonly RideRepositoryInterface $rideRepository,
        private readonly DriverRepositoryInterface $driverRepository,
        private readonly FraudControlService $fraud,
        private readonly Dispatcher $dispatcher,
        private readonly \Illuminate\Database\DatabaseManager $db, private readonly LoggerInterface $logger, private readonly Guard $guard) {

    }

    /** @throws Throwable */
    public function __invoke(RideId $rideId, DriverId $driverId, string $correlationId): void
    {
        $this->logger->info('AcceptRideUseCase started', [
            'correlation_id' => $correlationId,
            'ride_id' => $rideId->toString(),
            'driver_id' => $driverId->toString(),
        ]);

        $this->fraud->check(userId: $this->guard->id() ?? 0, operationType: 'mutation', amount: 0, correlationId: $correlationId ?? '');

        $this->db->transaction(function () use ($rideId, $driverId, $correlationId) {
            $ride = $this->rideRepository->findById($rideId);

            if ($ride === null) {
                throw new \DomainException("Ride {$rideId->toString()} not found.");
            }

            if ($ride->getStatus() !== RideStatusEnum::Requested) {
                throw new \DomainException("Ride {$rideId->toString()} is not in 'requested' status.");
            }

            $driver = $this->driverRepository->findById($driverId);

            if ($driver === null) {
                throw new \DomainException("Driver {$driverId->toString()} not found.");
            }

            $ride->assignDriver($driverId);
            $this->rideRepository->save($ride);

            $driver->setUnavailable();
            $this->driverRepository->save($driver);

            $this->dispatcher->dispatch(new RideAccepted(
                rideId: $rideId->toString(),
                driverId: $driverId->toString(),
                correlationId: $correlationId,
            ));

            $this->logger->info('Ride accepted', [
                'correlation_id' => $correlationId,
                'ride_id' => $rideId->toString(),
                'driver_id' => $driverId->toString(),
            ]);
        });
    }
}
