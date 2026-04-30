<?php

declare(strict_types=1);

namespace Modules\Taxi\Application\Services;

use Modules\Taxi\Application\DTOs\RideDto;
use Modules\Taxi\Application\UseCases\CreateRideUseCase;
use Modules\Taxi\Application\UseCases\CompleteRideUseCase;
use Modules\Taxi\Domain\Entities\Ride;
use Modules\Taxi\Domain\Repositories\RideRepositoryInterface;
use Modules\Taxi\Domain\Repositories\DriverRepositoryInterface;
use Modules\Taxi\Domain\ValueObjects\RideId;
use App\Traits\WithAuditLogging;
use App\Services\Security\AuditService;

final readonly class TaxiOrchestratorService
{
    use WithAuditLogging;

    public function __construct(
        private CreateRideUseCase $createRideUseCase,
        private CompleteRideUseCase $completeRideUseCase,
        private RideRepositoryInterface $rideRepository,
        private DriverRepositoryInterface $driverRepository,
        private readonly AuditService $auditService,
    ) {
    }

    public function createRide(RideDto $dto): Ride
    {
        return $this->createRideUseCase->execute($dto);
    }

    public function completeRide(int $rideId, float $actualPrice): Ride
    {
        return $this->completeRideUseCase->execute($rideId, $actualPrice);
    }

    public function getRide(int $rideId): ?Ride
    {
        return $this->rideRepository->findById(new RideId($rideId));
    }

    public function getUserRides(int $userId): array
    {
        return $this->rideRepository->findByUser($userId);
    }

    public function getDriverRides(int $driverId): array
    {
        return $this->rideRepository->findByDriver($driverId);
    }

    public function getAvailableDrivers(): array
    {
        return $this->driverRepository->findAvailableDrivers();
    }

    public function getRidesByStatus(string $status): array
    {
        return $this->rideRepository->findByStatus($status);
    }
}
