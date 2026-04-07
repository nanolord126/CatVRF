<?php

declare(strict_types=1);

namespace App\Domains\Auto\Taxi\Application\B2C\UseCases;


use Psr\Log\LoggerInterface;
use App\Domains\Auto\Taxi\Application\B2C\DTO\RideDTO;
use App\Domains\Auto\Taxi\Application\B2C\Mappers\RideMapper;
use App\Domains\Auto\Taxi\Domain\Repository\RideRepositoryInterface;
use App\Domains\Auto\Taxi\Domain\ValueObjects\RideId;
/**
 * Class TrackRideUseCase
 *
 * Part of the Auto vertical domain.
 * Follows CatVRF 9-layer architecture.
 *
 * Component of the CatVRF platform.
 * Follows strict coding standards:
 * - final class (no inheritance unless required)
 * - private readonly properties
 * - Constructor injection only
 * - correlation_id in all operations
 *
 * @package App\Domains\Auto\Taxi\Application\B2C\UseCases
 */
final class TrackRideUseCase
{
    public function __construct(
        private readonly RideRepositoryInterface $rideRepository,
        private readonly RideMapper $rideMapper, private readonly LoggerInterface $logger) {

    }

    /**
     * Handle __invoke operation.
     *
     * @throws \DomainException
     */
    public function __invoke(RideId $rideId, int $userId, string $correlationId): RideDTO
    {
        $this->logger->info('TrackRideUseCase called', [
            'correlation_id' => $correlationId,
            'ride_id' => $rideId->toString(),
            'user_id' => $userId,
        ]);

        $ride = $this->rideRepository->findById($rideId);

        if ($ride === null) {
            throw new \DomainException("Ride {$rideId->toString()} not found.");
        }

        if ($ride->getUserId() !== $userId) {
            throw new \DomainException("Access denied to ride {$rideId->toString()}.");
        }

        return $this->rideMapper->toDTO($ride);
    }
}
