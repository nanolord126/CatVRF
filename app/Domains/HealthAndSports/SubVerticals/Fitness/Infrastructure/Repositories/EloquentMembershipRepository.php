<?php

declare(strict_types=1);

namespace Modules\Fitness\Infrastructure\Repositories;

use Carbon\CarbonImmutable;
use Modules\Fitness\Domain\Entities\Membership;
use Modules\Fitness\Domain\Repositories\MembershipRepositoryInterface;
use Modules\Fitness\Infrastructure\Models\MembershipModel;

final class EloquentMembershipRepository implements MembershipRepositoryInterface
{
    public function findById(int $id): ?Membership
    {
        $model = MembershipModel::find($id);
        return $model?->toDomain();
    }

    public function findByClientId(int $clientId): array
    {
        return MembershipModel::where('client_id', $clientId)
            ->orderByDesc('created_at')
            ->get()
            ->map(fn (MembershipModel $model) => $model->toDomain())
            ->toArray();
    }

    public function findActiveByClientId(int $clientId): ?Membership
    {
        $model = MembershipModel::where('client_id', $clientId)
            ->where('status', 'active')
            ->where('end_date', '>', CarbonImmutable::now())
            ->first();
        return $model?->toDomain();
    }

    public function findByTenantId(int $tenantId): array
    {
        return MembershipModel::where('tenant_id', $tenantId)
            ->get()
            ->map(fn (MembershipModel $model) => $model->toDomain())
            ->toArray();
    }

    public function findExpiringSoon(int $tenantId, int $days = 7): array
    {
        $deadline = CarbonImmutable::now()->addDays($days);
        
        return MembershipModel::where('tenant_id', $tenantId)
            ->where('status', 'active')
            ->whereBetween('end_date', [CarbonImmutable::now(), $deadline])
            ->get()
            ->map(fn (MembershipModel $model) => $model->toDomain())
            ->toArray();
    }

    public function findByType(int $tenantId, string $type): array
    {
        return MembershipModel::where('tenant_id', $tenantId)
            ->where('type', $type)
            ->get()
            ->map(fn (MembershipModel $model) => $model->toDomain())
            ->toArray();
    }

    public function save(Membership $membership): Membership
    {
        $model = MembershipModel::fromDomain($membership);
        $model->save();
        return $model->toDomain();
    }

    public function delete(int $id): void
    {
        MembershipModel::destroy($id);
    }
}
