<?php

declare(strict_types=1);

namespace App\Domains\RealEstate\Infrastructure\Eloquent\Repositories;

use App\Domains\RealEstate\Domain\Entities\RealEstateAgent;
use App\Domains\RealEstate\Domain\Repository\AgentRepositoryInterface;
use App\Domains\RealEstate\Domain\ValueObjects\AgentId;
use App\Domains\RealEstate\Domain\ValueObjects\PropertyId;
use App\Domains\RealEstate\Infrastructure\Eloquent\Models\AgentModel;
use Illuminate\Support\Collection;
use Psr\Log\LoggerInterface;

final class EloquentAgentRepository implements AgentRepositoryInterface
{
    public function __construct(private readonly LoggerInterface $logger) {}
    public function findById(AgentId $id): ?RealEstateAgent
    {
        $model = AgentModel::withoutGlobalScope('tenant')->find($id->getValue());

        return $model !== null ? $this->toDomain($model) : null;
    }

    public function findByIdAndTenant(AgentId $id, int $tenantId): ?RealEstateAgent
    {
        $model = AgentModel::withoutGlobalScope('tenant')
            ->where('id', $id->getValue())
            ->where('tenant_id', $tenantId)
            ->first();

        return $model !== null ? $this->toDomain($model) : null;
    }

    public function findByUserId(int $userId): ?RealEstateAgent
    {
        $model = AgentModel::withoutGlobalScope('tenant')
            ->where('user_id', $userId)
            ->first();

        return $model !== null ? $this->toDomain($model) : null;
    }

    public function findActiveByTenant(int $tenantId): Collection
    {
        return AgentModel::withoutGlobalScope('tenant')
            ->where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->orderBy('full_name')
            ->get()
            ->map(fn (AgentModel $m) => $this->toDomain($m));
    }

    public function findByTenantId(int $tenantId): Collection
    {
        return AgentModel::withoutGlobalScope('tenant')
            ->where('tenant_id', $tenantId)
            ->orderBy('full_name')
            ->get()
            ->map(fn (AgentModel $m) => $this->toDomain($m));
    }

    public function save(RealEstateAgent $agent): void
    {
        // Collect assigned property IDs as JSON for storage in tags
        $data = [
            'id'              => $agent->getId()->getValue(),
            'tenant_id'       => $agent->getTenantId(),
            'user_id'         => $agent->getUserId(),
            'full_name'       => $agent->getFullName(),
            'license_number'  => $agent->getLicenseNumber(),
            'rating'          => $agent->getRating(),
            'deals_count'     => $agent->getDealsCount(),
            'is_active'       => $agent->isActive(),
            'correlation_id'  => $agent->getCorrelationId(),
            'tags'            => [
                'assigned_property_ids' => array_map(
                    fn (PropertyId $p) => $p->getValue(),
                    $agent->getAssignedPropertyIds()
                ),
            ],
        ];

        AgentModel::withoutGlobalScope('tenant')
            ->updateOrCreate(['id' => $data['id']], $data);

        $this->logger->info('AgentRepository::save', [
            'agent_id'       => $data['id'],
            'is_active'      => $data['is_active'],
            'correlation_id' => $data['correlation_id'],
        ]);
    }

    public function delete(AgentId $id): void
    {
        AgentModel::withoutGlobalScope('tenant')
            ->where('id', $id->getValue())
            ->delete();

        $this->logger->info('AgentRepository::delete', [
            'agent_id' => $id->getValue(),
        ]);
    }

    private function toDomain(AgentModel $model): RealEstateAgent
    {
        $assignedIds = [];
        if (is_array($model->tags['assigned_property_ids'] ?? null)) {
            foreach ($model->tags['assigned_property_ids'] as $pid) {
                $assignedIds[] = PropertyId::fromString($pid);
            }
        }

        return new RealEstateAgent(
            id:                  AgentId::fromString($model->id),
            tenantId:            $model->tenant_id,
            userId:              $model->user_id,
            fullName:            $model->full_name,
            licenseNumber:       $model->license_number,
            rating:              $model->rating,
            dealsCount:          $model->deals_count,
            isActive:            $model->is_active,
            assignedPropertyIds: $assignedIds,
            correlationId:       $model->correlation_id ?? '',
        );
    }
}
