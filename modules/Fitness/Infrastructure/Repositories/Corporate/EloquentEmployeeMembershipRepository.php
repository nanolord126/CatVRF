<?php

declare(strict_types=1);

namespace Modules\Fitness\Infrastructure\Repositories\Corporate;

use Modules\Fitness\Domain\Corporate\Entities\EmployeeMembership;
use Modules\Fitness\Domain\Corporate\Repositories\EmployeeMembershipRepositoryInterface;
use Modules\Fitness\Infrastructure\Models\Corporate\EmployeeMembershipModel;

final readonly class EloquentEmployeeMembershipRepository implements EmployeeMembershipRepositoryInterface
{
    public function save(EmployeeMembership $membership): EmployeeMembership
    {
        $model = $membership->id === 0
            ? EmployeeMembershipModel::fromDomain($membership)
            : EmployeeMembershipModel::where('id', $membership->id)->first();

        if ($model === null) {
            $model = EmployeeMembershipModel::fromDomain($membership);
        } else {
            $model->updateFromDomain($membership);
        }

        $model->save();

        return $model->toDomain();
    }

    public function findById(int $id): ?EmployeeMembership
    {
        $model = EmployeeMembershipModel::find($id);

        return $model?->toDomain();
    }

    public function findByCorporateEnrollmentId(int $corporateEnrollmentId): array
    {
        $models = EmployeeMembershipModel::where('corporate_enrollment_id', $corporateEnrollmentId)
            ->get();

        return $models->map(fn (EmployeeMembershipModel $model) => $model->toDomain())->toArray();
    }

    public function findByClientId(int $clientId): array
    {
        $models = EmployeeMembershipModel::where('client_id', $clientId)
            ->orderBy('created_at', 'desc')
            ->get();

        return $models->map(fn (EmployeeMembershipModel $model) => $model->toDomain())->toArray();
    }

    public function findActiveByCorporateEnrollmentId(int $corporateEnrollmentId): array
    {
        $models = EmployeeMembershipModel::where('corporate_enrollment_id', $corporateEnrollmentId)
            ->where('status', 'active')
            ->where('remaining_visits', '>', 0)
            ->get();

        return $models->map(fn (EmployeeMembershipModel $model) => $model->toDomain())->toArray();
    }

    public function findActiveByClientId(int $clientId): array
    {
        $models = EmployeeMembershipModel::where('client_id', $clientId)
            ->where('status', 'active')
            ->where('remaining_visits', '>', 0)
            ->get();

        return $models->map(fn (EmployeeMembershipModel $model) => $model->toDomain())->toArray();
    }

    public function delete(int $id): void
    {
        EmployeeMembershipModel::where('id', $id)->delete();
    }

    public function exists(int $id): bool
    {
        return EmployeeMembershipModel::where('id', $id)->exists();
    }
}
