<?php

declare(strict_types=1);

namespace Modules\CatCRM\Infrastructure\Repositories;

use Modules\CatCRM\Domain\Entities\B2BLead;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

final class B2BLeadRepository
{
    public function find(int $id): ?B2BLead
    {
        return B2BLead::with(['contacts', 'warehouse', 'assignedTo'])->find($id);
    }

    public function findByTenant(int $tenantId, int $page = 1, int $perPage = 20): LengthAwarePaginator
    {
        return B2BLead::where('tenant_id', $tenantId)
            ->with(['contacts', 'warehouse', 'assignedTo'])
            ->orderBy('created_at', 'desc')
            ->paginate($perPage, ['*'], 'page', $page);
    }

    public function findByVertical(int $tenantId, string $vertical, int $page = 1, int $perPage = 20): LengthAwarePaginator
    {
        return B2BLead::where('tenant_id', $tenantId)
            ->where('vertical_id', $vertical)
            ->with(['contacts', 'warehouse', 'assignedTo'])
            ->orderBy('created_at', 'desc')
            ->paginate($perPage, ['*'], 'page', $page);
    }

    public function findByStatus(int $tenantId, string $status, int $page = 1, int $perPage = 20): LengthAwarePaginator
    {
        return B2BLead::where('tenant_id', $tenantId)
            ->where('status', $status)
            ->with(['contacts', 'warehouse', 'assignedTo'])
            ->orderBy('created_at', 'desc')
            ->paginate($perPage, ['*'], 'page', $page);
    }

    public function findOverdue(int $tenantId): Collection
    {
        return B2BLead::where('tenant_id', $tenantId)
            ->where('expected_close_date', '<', now())
            ->whereIn('status', ['new', 'contacted', 'qualified'])
            ->with(['contacts', 'assignedTo'])
            ->get();
    }

    public function search(int $tenantId, string $query, int $page = 1, int $perPage = 20): LengthAwarePaginator
    {
        return B2BLead::where('tenant_id', $tenantId)
            ->where(function ($q) use ($query) {
                $q->where('company_name', 'like', "%{$query}%")
                    ->orWhere('contact_person', 'like', "%{$query}%")
                    ->orWhere('contact_email', 'like', "%{$query}%")
                    ->orWhere('contact_phone', 'like', "%{$query}%");
            })
            ->with(['contacts', 'warehouse', 'assignedTo'])
            ->orderBy('created_at', 'desc')
            ->paginate($perPage, ['*'], 'page', $page);
    }

    public function getStats(int $tenantId): array
    {
        $query = B2BLead::where('tenant_id', $tenantId);

        return [
            'total' => $query->count(),
            'new' => (clone $query)->where('status', 'new')->count(),
            'contacted' => (clone $query)->where('status', 'contacted')->count(),
            'qualified' => (clone $query)->where('status', 'qualified')->count(),
            'proposal' => (clone $query)->where('status', 'proposal')->count(),
            'converted' => (clone $query)->where('status', 'converted')->count(),
            'won' => (clone $query)->where('status', 'won')->count(),
            'lost' => (clone $query)->where('status', 'lost')->count(),
            'overdue' => (clone $query)
                ->where('expected_close_date', '<', now())
                ->whereIn('status', ['new', 'contacted', 'qualified'])
                ->count(),
        ];
    }
}
