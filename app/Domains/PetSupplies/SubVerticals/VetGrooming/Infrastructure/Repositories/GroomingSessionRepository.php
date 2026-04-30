<?php

declare(strict_types=1);

namespace Modules\VetGrooming\Infrastructure\Repositories;

use Modules\VetGrooming\Domain\Repositories\GroomingSessionRepositoryInterface;
use Modules\VetGrooming\Domain\Entities\GroomingSession;
use Modules\VetGrooming\Domain\Enums\GroomingStatus;
use Modules\VetGrooming\Domain\Enums\GroomingServiceType;
use Modules\VetGrooming\Domain\Enums\BehaviorRating;
use Modules\VetGrooming\Infrastructure\Models\GroomingSessionModel;
use Carbon\CarbonImmutable;

class GroomingSessionRepository implements GroomingSessionRepositoryInterface
{
    public function findById(int $id): ?GroomingSession
    {
        $model = GroomingSessionModel::find($id);
        return $model?->toDomain();
    }

    public function findByUuid(string $uuid): ?GroomingSession
    {
        $model = GroomingSessionModel::where('uuid', $uuid)->first();
        return $model?->toDomain();
    }

    public function findByPetId(int $petId): array
    {
        $models = GroomingSessionModel::where('pet_id', $petId)
            ->orderBy('started_at', 'desc')
            ->get();

        return $models->map(fn ($model) => $model->toDomain())->toArray();
    }

    public function findByPetIdWithPhotos(int $petId): array
    {
        $models = GroomingSessionModel::where('pet_id', $petId)
            ->where(function ($query) {
                $query->whereNotNull('before_photos')
                    ->orWhereNotNull('after_photos');
            })
            ->orderBy('started_at', 'desc')
            ->get();

        return $models->map(fn ($model) => $model->toDomain())->toArray();
    }

    public function findByGroomerId(int $groomerId): array
    {
        $models = GroomingSessionModel::where('groomer_id', $groomerId)
            ->orderBy('started_at', 'desc')
            ->get();

        return $models->map(fn ($model) => $model->toDomain())->toArray();
    }

    public function findByGroomerIdAndDate(int $groomerId, CarbonImmutable $date): array
    {
        $models = GroomingSessionModel::where('groomer_id', $groomerId)
            ->whereDate('started_at', $date->toDateString())
            ->orderBy('started_at', 'asc')
            ->get();

        return $models->map(fn ($model) => $model->toDomain())->toArray();
    }

    public function findByClinicId(int $clinicId): array
    {
        $models = GroomingSessionModel::where('clinic_id', $clinicId)
            ->orderBy('started_at', 'desc')
            ->get();

        return $models->map(fn ($model) => $model->toDomain())->toArray();
    }

    public function findByStatus(GroomingStatus $status, int $limit = 100): array
    {
        $models = GroomingSessionModel::where('status', $status->value)
            ->orderBy('started_at', 'desc')
            ->limit($limit)
            ->get();

        return $models->map(fn ($model) => $model->toDomain())->toArray();
    }

    public function findByStatusAndTenant(GroomingStatus $status, int $tenantId, int $limit = 100): array
    {
        $models = GroomingSessionModel::where('tenant_id', $tenantId)
            ->where('status', $status->value)
            ->orderBy('started_at', 'desc')
            ->limit($limit)
            ->get();

        return $models->map(fn ($model) => $model->toDomain())->toArray();
    }

    public function findScheduledByDateRange(CarbonImmutable $start, CarbonImmutable $end, int $tenantId): array
    {
        $models = GroomingSessionModel::where('tenant_id', $tenantId)
            ->where('status', GroomingStatus::SCHEDULED->value)
            ->whereBetween('started_at', [$start->toDateTimeString(), $end->toDateTimeString()])
            ->orderBy('started_at', 'asc')
            ->get();

        return $models->map(fn ($model) => $model->toDomain())->toArray();
    }

    public function findCompletedByPetId(int $petId): array
    {
        $models = GroomingSessionModel::where('pet_id', $petId)
            ->where('status', GroomingStatus::COMPLETED->value)
            ->orderBy('completed_at', 'desc')
            ->get();

        return $models->map(fn ($model) => $model->toDomain())->toArray();
    }

    public function findLastByPetId(int $petId): ?GroomingSession
    {
        $model = GroomingSessionModel::where('pet_id', $petId)
            ->orderBy('started_at', 'desc')
            ->first();

        return $model?->toDomain();
    }

    public function findByServiceType(GroomingServiceType $serviceType, int $limit = 100): array
    {
        $models = GroomingSessionModel::where('service_type', $serviceType->value)
            ->orderBy('started_at', 'desc')
            ->limit($limit)
            ->get();

        return $models->map(fn ($model) => $model->toDomain())->toArray();
    }

    public function findWithAggressiveBehavior(int $tenantId, int $limit = 50): array
    {
        $models = GroomingSessionModel::where('tenant_id', $tenantId)
            ->where('behavior_rating', BehaviorRating::AGGRESSIVE->value)
            ->orderBy('started_at', 'desc')
            ->limit($limit)
            ->get();

        return $models->map(fn ($model) => $model->toDomain())->toArray();
    }

    public function save(GroomingSession $session): GroomingSession
    {
        $model = GroomingSessionModel::fromDomain($session);
        $model->save();

        return $model->toDomain();
    }

    public function delete(int $id): void
    {
        GroomingSessionModel::destroy($id);
    }

    public function countByPetId(int $petId): int
    {
        return GroomingSessionModel::where('pet_id', $petId)->count();
    }

    public function countByGroomerIdAndDate(int $groomerId, CarbonImmutable $date): int
    {
        return GroomingSessionModel::where('groomer_id', $groomerId)
            ->whereDate('started_at', $date->toDateString())
            ->count();
    }
}
