<?php

declare(strict_types=1);

namespace App\Domains\RealEstate\Infrastructure\Eloquent\Repositories;

use App\Domains\RealEstate\Domain\Entities\Contract;
use App\Domains\RealEstate\Domain\Enums\ContractTypeEnum;
use App\Domains\RealEstate\Domain\Repository\ContractRepositoryInterface;
use App\Domains\RealEstate\Domain\ValueObjects\AgentId;
use App\Domains\RealEstate\Domain\ValueObjects\ContractId;
use App\Domains\RealEstate\Domain\ValueObjects\Price;
use App\Domains\RealEstate\Domain\ValueObjects\PropertyId;
use App\Domains\RealEstate\Infrastructure\Eloquent\Models\ContractModel;
use Illuminate\Support\Collection;
use Psr\Log\LoggerInterface;

final class EloquentContractRepository implements ContractRepositoryInterface
{
    public function __construct(
        private readonly LoggerInterface $logger) {}
    public function findById(ContractId $id): ?Contract
    {
        $model = ContractModel::withoutGlobalScope('tenant')->find($id->getValue());

        return $model !== null ? $this->toDomain($model) : null;
    }

    public function findByIdAndTenant(ContractId $id, int $tenantId): ?Contract
    {
        $model = ContractModel::withoutGlobalScope('tenant')
            ->where('id', $id->getValue())
            ->where('tenant_id', $tenantId)
            ->first();

        return $model !== null ? $this->toDomain($model) : null;
    }

    public function findByPropertyId(PropertyId $propertyId): Collection
    {
        return ContractModel::withoutGlobalScope('tenant')
            ->where('property_id', $propertyId->getValue())
            ->orderByDesc('created_at')
            ->get()
            ->map(fn (ContractModel $m) => $this->toDomain($m));
    }

    public function findByTenantId(int $tenantId): Collection
    {
        return ContractModel::withoutGlobalScope('tenant')
            ->where('tenant_id', $tenantId)
            ->orderByDesc('created_at')
            ->get()
            ->map(fn (ContractModel $m) => $this->toDomain($m));
    }

    public function findSignedByTenant(int $tenantId): Collection
    {
        return ContractModel::withoutGlobalScope('tenant')
            ->where('tenant_id', $tenantId)
            ->where('status', 'signed')
            ->orderByDesc('signed_at')
            ->get()
            ->map(fn (ContractModel $m) => $this->toDomain($m));
    }

    public function save(Contract $contract): void
    {
        $data = [
            'id'                    => $contract->getId()->getValue(),
            'tenant_id'             => $contract->getTenantId(),
            'property_id'           => $contract->getPropertyId()->getValue(),
            'agent_id'              => $contract->getAgentId()->getValue(),
            'client_id'             => $contract->getClientId(),
            'type'                  => $contract->getType()->value,
            'price_kopecks'         => $contract->getPrice()->getAmountKopecks(),
            'commission_kopecks'    => $contract->calculateCommission()->getAmountKopecks(),
            'status'                => $contract->getStatus(),
            'lease_duration_months' => $contract->getLeaseDurationMonths(),
            'document_url'          => $contract->getDocumentUrl(),
            'signed_at'             => $contract->getSignedAt()?->format('Y-m-d H:i:s'),
            'terminated_at'         => $contract->getTerminatedAt()?->format('Y-m-d H:i:s'),
            'correlation_id'        => $contract->getCorrelationId(),
        ];

        ContractModel::withoutGlobalScope('tenant')
            ->updateOrCreate(['id' => $data['id']], $data);

        $this->logger->info('ContractRepository::save', [
            'contract_id'    => $data['id'],
            'status'         => $data['status'],
            'correlation_id' => $data['correlation_id'],
        ]);
    }

    private function toDomain(ContractModel $model): Contract
    {
        $signedAt     = $model->signed_at !== null
            ? new \DateTimeImmutable($model->signed_at->format('Y-m-d H:i:s'))
            : null;

        $terminatedAt = $model->terminated_at !== null
            ? new \DateTimeImmutable($model->terminated_at->format('Y-m-d H:i:s'))
            : null;

        return new Contract(
            id:                    ContractId::fromString($model->id),
            tenantId:              $model->tenant_id,
            propertyId:            PropertyId::fromString($model->property_id),
            agentId:               AgentId::fromString($model->agent_id),
            clientId:              $model->client_id,
            type:                  ContractTypeEnum::from($model->type),
            price:                 Price::fromKopecks($model->price_kopecks),
            status:                $model->status,
            leaseDurationMonths:   $model->lease_duration_months,
            documentUrl:           $model->document_url,
            signedAt:              $signedAt,
            terminatedAt:          $terminatedAt,
            correlationId:         $model->correlation_id ?? '',
        );
    }
}
