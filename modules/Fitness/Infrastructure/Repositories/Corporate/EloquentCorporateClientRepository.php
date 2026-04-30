<?php

declare(strict_types=1);

namespace Modules\Fitness\Infrastructure\Repositories\Corporate;

use Modules\Fitness\Domain\Corporate\Entities\CorporateClient;
use Modules\Fitness\Domain\Corporate\Repositories\CorporateClientRepositoryInterface;
use Modules\Fitness\Infrastructure\Models\Corporate\CorporateClientModel;

final readonly class EloquentCorporateClientRepository implements CorporateClientRepositoryInterface
{
    public function save(CorporateClient $client): CorporateClient
    {
        $model = $client->id === 0
            ? CorporateClientModel::fromDomain($client)
            : CorporateClientModel::where('id', $client->id)->first();

        if ($model === null) {
            $model = CorporateClientModel::fromDomain($client);
        } else {
            $model->updateFromDomain($client);
        }

        $model->save();

        return $model->toDomain();
    }

    public function findById(int $id): ?CorporateClient
    {
        $model = CorporateClientModel::find($id);

        return $model?->toDomain();
    }

    public function findByTenantId(int $tenantId): array
    {
        $models = CorporateClientModel::where('tenant_id', $tenantId)->get();

        return $models->map(fn (CorporateClientModel $model) => $model->toDomain())->toArray();
    }

    public function findByInn(int $tenantId, string $inn): ?CorporateClient
    {
        $model = CorporateClientModel::where('tenant_id', $tenantId)
            ->where('inn', $inn)
            ->first();

        return $model?->toDomain();
    }

    public function findActiveByTenantId(int $tenantId): array
    {
        $models = CorporateClientModel::where('tenant_id', $tenantId)
            ->where(function ($query) {
                $query->whereNull('contract_end_date')
                    ->orWhere('contract_end_date', '>', now());
            })
            ->get();

        return $models->map(fn (CorporateClientModel $model) => $model->toDomain())->toArray();
    }

    public function delete(int $id): void
    {
        CorporateClientModel::where('id', $id)->delete();
    }

    public function exists(int $id): bool
    {
        return CorporateClientModel::where('id', $id)->exists();
    }
}
