<?php

declare(strict_types=1);

namespace App\Domains\Auto\Taxi\Application\B2B\UseCases;



use Illuminate\Contracts\Auth\Guard;
use Psr\Log\LoggerInterface;
use App\Domains\Auto\Taxi\Application\B2B\DTO\CreateDriverDTO;
use App\Domains\Auto\Taxi\Domain\Entities\Driver;
use App\Domains\Auto\Taxi\Domain\Repository\DriverRepositoryInterface;
use App\Domains\Auto\Taxi\Domain\ValueObjects\DriverId;
use App\Services\FraudControlService;
use Illuminate\Support\Str;
use Throwable;

final class CreateDriverUseCase
{
    public function __construct(private readonly DriverRepositoryInterface $driverRepository,
        private readonly FraudControlService $fraud,
        private readonly \Illuminate\Database\DatabaseManager $db, private readonly LoggerInterface $logger, private readonly Guard $guard) {

    }

    /**
     * @throws Throwable
     */
    public function __invoke(CreateDriverDTO $dto): Driver
    {
        $correlationId = $dto->correlationId ?? Str::uuid()->toString();

        $this->logger->info('CreateDriverUseCase started', [
            'correlation_id' => $correlationId,
            'dto' => (array) $dto,
        ]);

        $this->fraud->check(userId: $this->guard->id() ?? 0, operationType: 'tenant_id', amount: 0, correlationId: $correlationId ?? '');

        return $this->db->transaction(function () use ($dto, $correlationId) {
            $driver = new Driver(
                id: new DriverId(Str::uuid()->toString()),
                name: $dto->name,
                licenseNumber: $dto->licenseNumber,
                isAvailable: true,
                vehicleId: null,
                createdAt: new \DateTimeImmutable(),
                updatedAt: new \DateTimeImmutable()
            );

            $this->driverRepository->save($driver);

            $this->logger->info('Driver created successfully', [
                'correlation_id' => $correlationId,
                'driver_id' => $driver->getId()->toString(),
            ]);

            return $driver;
        });
    }
}
