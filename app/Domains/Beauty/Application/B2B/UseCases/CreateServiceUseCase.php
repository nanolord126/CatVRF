<?php

declare(strict_types=1);

namespace App\Domains\Beauty\Application\B2B\UseCases;


use Psr\Log\LoggerInterface;
use App\Domains\Beauty\Application\B2B\DTOs\CreateServiceDTO;
use App\Domains\Beauty\Domain\Entities\Service;
use App\Domains\Beauty\Domain\Enums\ServiceCategory;
use App\Domains\Beauty\Domain\Events\ServiceCreated;
use App\Domains\Beauty\Domain\Repositories\ServiceRepositoryInterface;
use App\Domains\Beauty\Domain\ValueObjects\Duration;
use App\Domains\Beauty\Domain\ValueObjects\Price;
use App\Services\FraudControlService;
use App\Shared\Domain\ValueObjects\TenantId;
use Illuminate\Contracts\Events\Dispatcher as EventDispatcher;

/**
 * B2B: Создать новую услугу в каталоге салона.
 */
final readonly class CreateServiceUseCase
{
    public function __construct(
        private ServiceRepositoryInterface $serviceRepository,
        private FraudControlService $fraud,
        private \Illuminate\Database\DatabaseManager $db,
        private LoggerInterface $logger,
        private EventDispatcher $events,
    ) {
    }

    public function handle(CreateServiceDTO $dto): Service
    {
        $fraud = $this->fraud->check(
            userId: 0,
            operationType: 'beauty.service.create',
            amount: $dto->priceRubles * 100,
            correlationId: $dto->correlationId,
        );

        if ($fraud['decision'] === 'block') {
            throw new \DomainException('Operation blocked by fraud control. Correlation: ' . $dto->correlationId);
        }

        return $this->db->transaction(function () use ($dto): Service {
            $serviceId = $this->serviceRepository->nextIdentity();

            $service = new Service(
                id: $serviceId,
                name: $dto->name,
                category: ServiceCategory::from($dto->category),
                price: Price::fromRubles($dto->priceRubles),
                duration: Duration::fromMinutes($dto->durationMinutes),
                description: $dto->description,
                isActive: true,
                createdAt: new \DateTimeImmutable(),
                updatedAt: new \DateTimeImmutable(),
            );

            $this->serviceRepository->save($service);

            $this->events->dispatch(new ServiceCreated(
                serviceId: $serviceId,
                tenantId: new TenantId($dto->tenantId),
                correlationId: $dto->correlationId,
            ));

            $this->logger->info('B2B: Service created', [
                'correlation_id' => $dto->correlationId,
                'service_id'     => $serviceId->getValue(),
                'tenant_id'      => $dto->tenantId,
                'name'           => $dto->name,
            ]);

            return $service;
        });
    }
}
