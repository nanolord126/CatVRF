<?php

declare(strict_types=1);

namespace App\Domains\RealEstate\Application\B2B\UseCases;

use App\Domains\RealEstate\Domain\Repository\ContractRepositoryInterface;
use App\Domains\RealEstate\Domain\ValueObjects\ContractId;
use App\Services\FraudControlService;
use Illuminate\Database\ConnectionInterface;
use Psr\Log\LoggerInterface;
use RuntimeException;

final class SignContractUseCase
{
    public function __construct(
        private readonly ContractRepositoryInterface $contractRepository,
        private readonly FraudControlService         $fraud,
        private readonly ConnectionInterface         $db,
        private readonly LoggerInterface             $logger,
    ) {}

    /**
     * Signs a pending contract — changes status to Signed, records signature metadata.
     *
     * @throws RuntimeException
     */
    public function handle(
        string  $contractId,
        int     $signerUserId,
        int     $tenantId,
        string  $signatureDocumentUrl,
        string  $correlationId,
        ?string $ipAddress = null,
        ?string $deviceFingerprint = null,
    ): void {
        $this->fraud->check(
            userId: $signerUserId,
            operationType: 'real_estate.contract.sign',
            amount: 0,
            ipAddress: $ipAddress,
            deviceFingerprint: $deviceFingerprint,
            correlationId: $correlationId,
        );

        $this->logger->info('RealEstate.SignContract started', [
            'correlation_id' => $correlationId,
            'contract_id'    => $contractId,
            'signer_user_id' => $signerUserId,
            'tenant_id'      => $tenantId,
        ]);

        $contract = $this->contractRepository->findByIdAndTenant(
            ContractId::fromString($contractId),
            $tenantId,
        );

        if ($contract === null) {
            throw new RuntimeException(
                "Contract {$contractId} not found for tenant {$tenantId}."
            );
        }

        if ($contract->isSigned()) {
            throw new RuntimeException(
                "Contract {$contractId} is already signed."
            );
        }

        if ($contract->isCancelled()) {
            throw new RuntimeException(
                "Contract {$contractId} has been cancelled and cannot be signed."
            );
        }

        $contract->sign(
            signerUserId: $signerUserId,
            signatureDocumentUrl: $signatureDocumentUrl,
            correlationId: $correlationId,
        );

        $events = $contract->pullDomainEvents();

        $this->db->transaction(function () use ($contract): void {
            $this->contractRepository->save($contract);
        });

        foreach ($events as $event) {
            event($event);
        }

        $this->logger->info('RealEstate.SignContract completed', [
            'correlation_id'        => $correlationId,
            'contract_id'           => $contractId,
            'signer_user_id'        => $signerUserId,
            'signature_document_url' => $signatureDocumentUrl,
        ]);
    }
}
