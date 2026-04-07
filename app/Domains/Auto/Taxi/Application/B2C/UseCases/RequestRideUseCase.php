<?php

declare(strict_types=1);

namespace App\Domains\Auto\Taxi\Application\B2C\UseCases;



use Illuminate\Contracts\Auth\Guard;
use Psr\Log\LoggerInterface;
use App\Domains\Auto\Taxi\Application\Shared\DTOs\RequestRideDTO;
use App\Domains\Auto\Taxi\Application\Shared\DTOs\RideDTO;
use App\Domains\Auto\Taxi\Application\Shared\Mappers\RideMapper;
use App\Domains\Auto\Taxi\Domain\Entities\Ride;
use App\Domains\Auto\Taxi\Domain\Repository\RideRepositoryInterface;
use App\Domains\Auto\Taxi\Domain\Services\PricingService;
use App\Services\FraudControlService;
use Throwable;

final class RequestRideUseCase
{
    public function __construct(private readonly RideRepositoryInterface $rideRepository,
        private readonly PricingService $pricingService,
        private readonly RideMapper $rideMapper,
        private readonly FraudControlService $fraud,
        private readonly \Illuminate\Database\DatabaseManager $db, private readonly LoggerInterface $logger, private readonly Guard $guard) {

    }

    /**
     * @throws Throwable
     */
    public function __invoke(RequestRideDTO $requestRideDTO): RideDTO
    {
        $this->logger->info('RequestRideUseCase started', [
            'correlation_id' => $requestRideDTO->correlationId,
            'client_id' => $requestRideDTO->clientId,
        ]);

        $this->fraud->check(userId: $this->guard->id() ?? 0, operationType: 'ip', amount: 0, correlationId: $correlationId ?? '');

        // In a real application, we would also use a rate limiter here.

        return $this->db->transaction(function () use ($requestRideDTO) {
            $rideId = $this->rideRepository->getNextId();

            $estimatedPrice = $this->pricingService->calculateEstimatedPrice(
                $requestRideDTO->pickupLocation,
                $requestRideDTO->dropoffLocation
            );

            $ride = Ride::request(
                id: $rideId,
                clientId: $requestRideDTO->clientId,
                pickupLocation: $requestRideDTO->pickupLocation,
                dropoffLocation: $requestRideDTO->dropoffLocation,
            );
            
            // In a real scenario, we would find a driver and accept the ride.
            // For now, we just save it as pending.
            // A background job would be dispatched to find a suitable driver.

            $this->rideRepository->save($ride);

            $this->logger->info('Ride requested successfully', [
                'correlation_id' => $requestRideDTO->correlationId,
                'ride_id' => $ride->getId()->toString(),
                'estimated_price' => $estimatedPrice,
            ]);

            return $this->rideMapper->toDTO($ride, $requestRideDTO->correlationId);
        });
    }
}
