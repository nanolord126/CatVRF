<?php

declare(strict_types=1);

namespace Modules\CatCRM\Infrastructure\Repositories;

use Modules\CatCRM\Domain\Entities\Customer;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

final class CustomerRepository
{
    public function find(int $id): ?Customer
    {
        return Customer::with(['deals', 'interactions', 'tasks', 'tags', 'segments'])->find($id);
    }

    public function findByTenant(int $tenantId, int $page = 1, int $perPage = 20): LengthAwarePaginator
    {
        return Customer::where('tenant_id', $tenantId)
            ->with(['deals', 'tags'])
            ->orderBy('created_at', 'desc')
            ->paginate($perPage, ['*'], 'page', $page);
    }

    public function findByType(int $tenantId, string $type, int $page = 1, int $perPage = 20): LengthAwarePaginator
    {
        return Customer::where('tenant_id', $tenantId)
            ->where('type', $type)
            ->with(['deals', 'tags'])
            ->orderBy('created_at', 'desc')
            ->paginate($perPage, ['*'], 'page', $page);
    }

    public function findVip(int $tenantId, int $page = 1, int $perPage = 20): LengthAwarePaginator
    {
        return Customer::where('tenant_id', $tenantId)
            ->where('is_vip', true)
            ->with(['deals', 'tags'])
            ->orderBy('total_spent', 'desc')
            ->paginate($perPage, ['*'], 'page', $page);
    }

    public function findSleeping(int $tenantId, int $days = 60, int $page = 1, int $perPage = 20): LengthAwarePaginator
    {
        return Customer::where('tenant_id', $tenantId)
            ->where('last_interaction_at', '<', now()->subDays($days))
            ->with(['deals', 'tags'])
            ->orderBy('last_interaction_at', 'asc')
            ->paginate($perPage, ['*'], 'page', $page);
    }

    public function search(int $tenantId, string $query, int $page = 1, int $perPage = 20): LengthAwarePaginator
    {
        return Customer::where('tenant_id', $tenantId)
            ->where(function ($q) use ($query) {
                $q->where('first_name', 'like', "%{$query}%")
                    ->orWhere('last_name', 'like', "%{$query}%")
                    ->orWhere('company_name', 'like', "%{$query}%")
                    ->orWhere('email', 'like', "%{$query}%")
                    ->orWhere('phone', 'like', "%{$query}%");
            })
            ->with(['deals', 'tags'])
            ->orderBy('created_at', 'desc')
            ->paginate($perPage, ['*'], 'page', $page);
    }

    public function getTopByLTV(int $tenantId, int $limit = 10): Collection
    {
        return Customer::where('tenant_id', $tenantId)
            ->orderBy('total_spent', 'desc')
            ->limit($limit)
            ->get();
    }
}
