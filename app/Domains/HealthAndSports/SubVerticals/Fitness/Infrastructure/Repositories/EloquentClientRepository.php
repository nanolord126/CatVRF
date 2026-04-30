<?php

declare(strict_types=1);

namespace Modules\Fitness\Infrastructure\Repositories;

use Modules\Fitness\Domain\Entities\Client;
use Modules\Fitness\Domain\Repositories\ClientRepositoryInterface;
use Modules\Fitness\Infrastructure\Models\ClientModel;

final class EloquentClientRepository implements ClientRepositoryInterface
{
    public function findById(int $id): ?Client
    {
        $model = ClientModel::find($id);
        return $model?->toDomain();
    }

    public function findByUserId(int $userId): ?Client
    {
        $model = ClientModel::where('user_id', $userId)->first();
        return $model?->toDomain();
    }

    public function findByTenantId(int $tenantId): array
    {
        return ClientModel::where('tenant_id', $tenantId)
            ->get()
            ->map(fn (ClientModel $model) => $model->toDomain())
            ->toArray();
    }

    public function findByPhone(int $tenantId, string $phone): ?Client
    {
        $model = ClientModel::where('tenant_id', $tenantId)
            ->where('phone', $phone)
            ->first();
        return $model?->toDomain();
    }

    public function findByEmail(int $tenantId, string $email): ?Client
    {
        $model = ClientModel::where('tenant_id', $tenantId)
            ->where('email', $email)
            ->first();
        return $model?->toDomain();
    }

    public function searchByName(int $tenantId, string $name): array
    {
        return ClientModel::where('tenant_id', $tenantId)
            ->where(function ($q) use ($name) {
                $q->where('first_name', 'like', "%{$name}%")
                    ->orWhere('last_name', 'like', "%{$name}%");
            })
            ->get()
            ->map(fn (ClientModel $model) => $model->toDomain())
            ->toArray();
    }

    public function findTopByVisits(int $tenantId, int $limit = 10): array
    {
        return ClientModel::where('tenant_id', $tenantId)
            ->orderByDesc('total_visits')
            ->limit($limit)
            ->get()
            ->map(fn (ClientModel $model) => $model->toDomain())
            ->toArray();
    }

    public function save(Client $client): Client
    {
        $model = ClientModel::fromDomain($client);
        $model->save();
        return $model->toDomain();
    }

    public function delete(int $id): void
    {
        ClientModel::destroy($id);
    }
}
