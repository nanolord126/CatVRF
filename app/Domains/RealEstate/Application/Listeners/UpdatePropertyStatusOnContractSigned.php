<?php

declare(strict_types=1);

namespace App\Domains\RealEstate\Application\Listeners;

use App\Domains\RealEstate\Domain\Events\ContractSigned;
use App\Domains\RealEstate\Domain\Repository\PropertyRepositoryInterface;
use App\Domains\RealEstate\Domain\ValueObjects\PropertyId;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Psr\Log\LoggerInterface;

final class UpdatePropertyStatusOnContractSigned implements ShouldQueue
{
    use InteractsWithQueue;

    public string $queue = 'default';

    public int $tries = 5;

    public int $backoff = 30;

    public function __construct(
        private readonly PropertyRepositoryInterface $propertyRepository) {}

    public function handle(ContractSigned $event, LoggerInterface $logger): void
    {
        $correlationId = $event->correlationId;

        $logger->info('UpdatePropertyStatusOnContractSigned: handling event', [
            'contract_id'    => $event->contractId->toString(),
            'property_id'    => $event->propertyId->toString(),
            'contract_type'  => $event->contractType->value,
            'correlation_id' => $correlationId,
        ]);

        try {
            $propertyId = PropertyId::fromString($event->propertyId->toString());
            $property   = $this->propertyRepository->findById($propertyId);

            if ($property === null) {
                $logger->warning('UpdatePropertyStatusOnContractSigned: property not found', [
                    'property_id'    => $event->propertyId->toString(),
                    'correlation_id' => $correlationId,
                ]);

                return;
            }

            $targetStatus = $event->contractType->resultingPropertyStatus();

            if ($targetStatus->value === 'sold') {
                $property->markAsSold($correlationId);
            } elseif ($targetStatus->value === 'rented') {
                $property->markAsRented($correlationId);
            }

            $this->propertyRepository->save($property);

            $logger->info('UpdatePropertyStatusOnContractSigned: property status updated', [
                'property_id'    => $event->propertyId->toString(),
                'new_status'     => $targetStatus->value,
                'correlation_id' => $correlationId,
            ]);
        } catch (\Throwable $e) {
            $logger->error('UpdatePropertyStatusOnContractSigned: failed', [
                'contract_id'    => $event->contractId->toString(),
                'property_id'    => $event->propertyId->toString(),
                'correlation_id' => $correlationId,
                'error'          => $e->getMessage(),
                'trace'          => $e->getTraceAsString(),
            ]);

            $this->fail($e);
        }
    }

    public function failed(ContractSigned $event, \Throwable $exception): void
    {
        report(new \RuntimeException(
            sprintf(
                'UpdatePropertyStatusOnContractSigned permanently failed [contract_id=%s, property_id=%s, correlation_id=%s]: %s',
                $event->contractId->toString(),
                $event->propertyId->toString(),
                $event->correlationId,
                $exception->getMessage(),
            ),
            previous: $exception,
        ));
    }
}
