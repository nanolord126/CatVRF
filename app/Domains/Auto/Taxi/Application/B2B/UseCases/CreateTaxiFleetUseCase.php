<?php

declare(strict_types=1);

namespace App\Domains\Auto\Taxi\Application\B2B\UseCases;



use Illuminate\Contracts\Auth\Guard;
use Psr\Log\LoggerInterface;
use App\Domains\Auto\Taxi\Application\B2B\DTO\CreateTaxiFleetDTO;
use App\Domains\Auto\Taxi\Domain\Entities\TaxiFleet;
use App\Domains\Auto\Taxi\Domain\Repository\TaxiFleetRepositoryInterface;
use App\Domains\Auto\Taxi\Domain\ValueObjects\TaxiFleetId;
use App\Services\FraudControlService;
use Illuminate\Support\Str;
use Throwable;

final class CreateTaxiFleetUseCase
{
    public function __construct(private readonly TaxiFleetRepositoryInterface $fleetRepository,
        private readonly FraudControlService $fraud,
        private readonly \Illuminate\Database\DatabaseManager $db, private readonly LoggerInterface $logger, private readonly Guard $guard) {

    }

    /**
     * @throws Throwable
     */
    public function __invoke(CreateTaxiFleetDTO $dto): TaxiFleet
    {
        $correlationId = $dto->correlationId ?? Str::uuid()->toString();

        $this->logger->info('CreateTaxiFleetUseCase started', [
            'correlation_id' => $correlationId,
            'dto' => (array) $dto,
        ]);

        $this->fraud->check(userId: $this->guard->id() ?? 0, operationType: 'tenant_id', amount: 0, correlationId: $correlationId ?? '');

        return $this->db->transaction(function () use ($dto, $correlationId) {
            $fleet = new TaxiFleet(
                id: new TaxiFleetId(Str::uuid()->toString()),
                tenantId: $dto->tenantId,
                name: $dto->name,
                createdAt: new \DateTimeImmutable(),
                updatedAt: new \DateTimeImmutable()
            );

            $this->fleetRepository->save($fleet);

            $this->logger->info('TaxiFleet created successfully', [
                'correlation_id' => $correlationId,
                'fleet_id' => $fleet->getId()->toString(),
            ]);

            return $fleet;
        });
    }
}
