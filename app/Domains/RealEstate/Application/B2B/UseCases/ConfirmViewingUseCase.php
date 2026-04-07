<?php

declare(strict_types=1);

namespace App\Domains\RealEstate\Application\B2B\UseCases;

use App\Domains\RealEstate\Application\B2B\DTOs\ConfirmViewingDTO;
use App\Domains\RealEstate\Domain\Repository\ViewingRepositoryInterface;
use App\Domains\RealEstate\Domain\ValueObjects\ViewingId;
use App\Services\FraudControlService;
use Illuminate\Database\ConnectionInterface;
use Psr\Log\LoggerInterface;
use RuntimeException;

final class ConfirmViewingUseCase
{
    public function __construct(
        private readonly ViewingRepositoryInterface $viewingRepository,
        private readonly FraudControlService        $fraud,
        private readonly ConnectionInterface        $db,
        private readonly LoggerInterface            $logger) {}

    /**
     * Agent confirms the viewing appointment, optionally rescheduling it.
     *
     * @throws RuntimeException
     */
    public function handle(ConfirmViewingDTO $dto): void
    {
        $this->fraud->check(
            userId: $dto->agentUserId,
            operationType: 'real_estate.viewing.confirm',
            amount: 0,
            ipAddress: $dto->ipAddress,
            deviceFingerprint: $dto->deviceFingerprint,
            correlationId: $dto->correlationId,
        );

        $this->logger->info('RealEstate.ConfirmViewing started', [
            'correlation_id' => $dto->correlationId,
            'viewing_id'     => $dto->viewingId,
            'tenant_id'      => $dto->tenantId,
        ]);

        $viewing = $this->viewingRepository->findByIdAndTenant(
            ViewingId::fromString($dto->viewingId),
            $dto->tenantId,
        );

        if ($viewing === null) {
            throw new RuntimeException("Viewing {$dto->viewingId} not found.");
        }

        if ($dto->rescheduledAt !== null) {
            $viewing->reschedule($dto->rescheduledAt);
        }

        $viewing->confirm($dto->correlationId);

        $events = $viewing->pullDomainEvents();

        $this->db->transaction(function () use ($viewing): void {
            $this->viewingRepository->save($viewing);
        });

        foreach ($events as $event) {
            event($event);
        }

        $this->logger->info('RealEstate.ConfirmViewing completed', [
            'correlation_id' => $dto->correlationId,
            'viewing_id'     => $dto->viewingId,
        ]);
    }
}
