<?php

declare(strict_types=1);

namespace App\Domains\RealEstate\Application\Jobs;


use App\Domains\RealEstate\Domain\Repository\ContractRepositoryInterface;
use App\Domains\RealEstate\Domain\ValueObjects\ContractId;
use App\Services\WalletService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Psr\Log\LoggerInterface;

final class ProcessCommissionJob implements ShouldQueue
{

    public int $tries = 5;

    public int $backoff = 120;


    public function __construct(
        private readonly string $contractId,
        private readonly int    $tenantId,
        private readonly int    $commissionKopecks,
        private readonly string $correlationId) {}

    public function handle(
        ContractRepositoryInterface $contractRepository,
        WalletService $walletService,
        ConnectionInterface $db,
        LoggerInterface $logger,
    ): void {
        $logger->info('ProcessCommissionJob: started', [
            'contract_id'       => $this->contractId,
            'tenant_id'         => $this->tenantId,
            'commission_kopecks' => $this->commissionKopecks,
            'correlation_id'    => $this->correlationId,
        ]);

        try {
            $db->transaction(function () use ($contractRepository, $walletService, $logger): void {
                $contractDomainId = ContractId::fromString($this->contractId);
                $contract         = $contractRepository->findById($contractDomainId);

                if ($contract === null) {
                    throw new \RuntimeException('Contract not found: ' . $this->contractId);
                }

                if ($contract->getStatus()->value !== 'signed') {
                    $logger->warning('ProcessCommissionJob: contract not signed, skipping', [
                        'contract_id'    => $this->contractId,
                        'status'         => $contract->getStatus()->value,
                        'correlation_id' => $this->correlationId,
                    ]);

                    return;
                }

                $walletService->debit(
                    tenantId:      $this->tenantId,
                    amountKopecks: $this->commissionKopecks,
                    type:          'commission',
                    sourceType:    'real_estate_contract',
                    sourceId:      $this->contractId,
                    correlationId: $this->correlationId,
                    description:   'Комиссия платформы за сделку с недвижимостью',
                );
            });

            $logger->info('ProcessCommissionJob: commission debited', [
                'contract_id'       => $this->contractId,
                'commission_kopecks' => $this->commissionKopecks,
                'correlation_id'    => $this->correlationId,
            ]);
        } catch (\Throwable $e) {
            $logger->error('ProcessCommissionJob: failed to debit commission', [
                'contract_id'    => $this->contractId,
                'correlation_id' => $this->correlationId,
                'error'          => $e->getMessage(),
                'trace'          => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    public function failed(\Throwable $exception): void
    {
        report(new \RuntimeException(
            sprintf(
                'ProcessCommissionJob permanently failed [contract_id=%s, correlation_id=%s]: %s',
                $this->contractId,
                $this->correlationId,
                $exception->getMessage(),
            ),
            previous: $exception,
        ));
    }

    public function tags(): array
    {
        return [
            'real-estate',
            'commission',
            'tenant:' . $this->tenantId,
            'contract:' . $this->contractId,
        ];
    }

    public function retryUntil(): \DateTimeInterface
    {
        return now()->addHours(24);
    }
}
