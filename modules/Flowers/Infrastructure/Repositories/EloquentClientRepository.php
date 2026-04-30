<?php

declare(strict_types=1);

namespace Modules\Flowers\Infrastructure\Repositories;

use Modules\Flowers\Domain\Repositories\ClientRepositoryInterface;
use Modules\Flowers\Domain\Entities\Client;
use Modules\Flowers\Infrastructure\Models\ClientModel;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

final class EloquentClientRepository implements ClientRepositoryInterface
{
    public function findById(int $id): ?Client
    {
        $model = ClientModel::find($id);
        return $model?->toDomain();
    }

    public function findByPhone(string $phone): ?Client
    {
        $model = ClientModel::byPhone($phone)->first();
        return $model?->toDomain();
    }

    public function findByEmail(string $email): ?Client
    {
        $model = ClientModel::byEmail($email)->first();
        return $model?->toDomain();
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

    public function getByTenant(int $tenantId, array $filters = []): LengthAwarePaginator
    {
        $query = ClientModel::where('tenant_id', $tenantId);

        $this->applyFilters($query, $filters);

        return $query->orderBy('created_at', 'desc')
            ->paginate(20);
    }

    public function getVipClients(int $tenantId): array
    {
        return ClientModel::where('tenant_id', $tenantId)
            ->vip()
            ->orderBy('total_spent', 'desc')
            ->get()
            ->map(fn ($model) => $model->toDomain())
            ->toArray();
    }

    public function getLoyalClients(int $tenantId): array
    {
        return ClientModel::where('tenant_id', $tenantId)
            ->loyal()
            ->orderBy('total_orders', 'desc')
            ->get()
            ->map(fn ($model) => $model->toDomain())
            ->toArray();
    }

    public function getClientsWithBirthdaySoon(int $tenantId, int $days = 7): array
    {
        return ClientModel::where('tenant_id', $tenantId)
            ->withBirthdaySoon($days)
            ->orderBy('birth_date')
            ->get()
            ->map(fn ($model) => $model->toDomain())
            ->toArray();
    }

    private function applyFilters(Builder $query, array $filters): void
    {
        if (isset($filters['loyalty_tier'])) {
            $query->where('loyalty_tier', $filters['loyalty_tier']);
        }

        if (isset($filters['is_subscribed'])) {
            $query->where('is_subscribed', $filters['is_subscribed']);
        }

        if (isset($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }
    }
}
