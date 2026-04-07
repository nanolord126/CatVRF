<?php

declare(strict_types=1);

namespace App\Domains\Beauty\Infrastructure\Persistence\Eloquent\Repositories;


use App\Services\FraudControlService;
use App\Domains\Beauty\Domain\Entities\Service;
use App\Domains\Beauty\Domain\Repositories\ServiceRepositoryInterface;
use App\Domains\Beauty\Domain\ValueObjects\ServiceId;
use App\Domains\Beauty\Infrastructure\Persistence\Eloquent\Mappers\ServiceMapper;
use App\Domains\Beauty\Infrastructure\Persistence\Eloquent\Models\BeautyService as EloquentService;
use App\Shared\Domain\ValueObjects\TenantId;
use Illuminate\Support\Collection;

/**
 * Eloquent-реализация репозитория услуг Beauty.
 *
 * @package App\Domains\Beauty\Infrastructure\Persistence\Eloquent\Repositories
 */
final class EloquentServiceRepository implements ServiceRepositoryInterface
{
    public function __construct(
        private FraudControlService $fraud,
        private EloquentService $eloquentServiceModel,
        private ServiceMapper $serviceMapper,
    ) {}

    public function findById(ServiceId $id): ?Service
    {
        $eloquentService = $this->eloquentServiceModel->where('uuid', $id->getValue())->first();
        return $eloquentService ? $this->serviceMapper->toDomain($eloquentService) : null;
    }

    public function findByTenantId(TenantId $tenantId): Collection
    {
        $eloquentServices = $this->eloquentServiceModel->where('tenant_id', $tenantId->getValue())->get();
        return $eloquentServices->map(fn(EloquentService $s) => $this->serviceMapper->toDomain($s));
    }

    public function save(Service $service): void
    {
        $this->fraud->check(new \App\DTOs\OperationDto(correlationId: \Illuminate\Support\Str::uuid()->toString()));

        $eloquentService = $this->serviceMapper->toEloquent($service);
        $eloquentService->save();
    }

    public function delete(ServiceId $id): bool
    {
        $this->fraud->check(new \App\DTOs\OperationDto(correlationId: \Illuminate\Support\Str::uuid()->toString()));

        return (bool)$this->eloquentServiceModel->where('uuid', $id->getValue())->delete();
    }

    public function nextIdentity(): ServiceId
    {
        return ServiceId::generate();
    }
}
