<?php

declare(strict_types=1);

namespace App\Domains\Auto\Taxi\Application\B2B\UseCases;



use Illuminate\Contracts\Auth\Guard;
use Psr\Log\LoggerInterface;
use App\Domains\Auto\Taxi\Domain\Repository\DriverRepositoryInterface;
use App\Domains\Auto\Taxi\Domain\Repository\TaxiFleetRepositoryInterface;
use App\Domains\Auto\Taxi\Domain\ValueObjects\DriverId;
use App\Domains\Auto\Taxi\Domain\ValueObjects\TaxiFleetId;
use App\Services\FraudControlService;
use Throwable;

final class AddDriverToFleetUseCase
{
    public function __construct(private readonly TaxiFleetRepositoryInterface $fleetRepository,
        private readonly DriverRepositoryInterface $driverRepository,
        private readonly FraudControlService $fraud,
        private readonly \Illuminate\Database\DatabaseManager $db, private readonly LoggerInterface $logger, private readonly Guard $guard) {

    }

    /**
     * @throws Throwable
     */
    public function __invoke(TaxiFleetId $fleetId, DriverId $driverId, string $correlationId): void
    {
        $this->logger->info('AddDriverToFleetUseCase started', [
            'correlation_id' => $correlationId,
            'fleet_id' => $fleetId->toString(),
            'driver_id' => $driverId->toString(),
        ]);

        $this->fraud->check(userId: $this->guard->id() ?? 0, operationType: 'mutation', amount: 0, correlationId: $correlationId ?? '');

        $this->db->transaction(function () use ($fleetId, $driverId, $correlationId) {
            $fleet = $this->fleetRepository->findById($fleetId);
            if (!$fleet) {
                throw new \RuntimeException("TaxiFleet with ID {$fleetId->toString()} not found.");
            }

            $driver = $this->driverRepository->findById($driverId);
            if (!$driver) {
                throw new \RuntimeException("Driver with ID {$driverId->toString()} not found.");
            }

            $fleet->addDriver($driverId);
            $this->fleetRepository->save($fleet);

            $this->logger->info('Driver added to fleet successfully', [
                'correlation_id' => $correlationId,
                'fleet_id' => $fleetId->toString(),
                'driver_id' => $driverId->toString(),
            ]);
        });
    }
}
