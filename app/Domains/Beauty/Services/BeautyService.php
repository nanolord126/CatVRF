<?php

declare(strict_types=1);

namespace App\Domains\Beauty\Services;

use App\Domains\Beauty\DTOs\CreateServiceDto;
use App\Domains\Beauty\Models\BeautyService as BeautyServiceModel;
use App\Services\AuditService;
use App\Services\FraudControlService;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Database\DatabaseManager;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;
use Psr\Log\LoggerInterface;

/**
 * BeautyService — CRUD и управление услугами салона красоты.
 *
 * CANON 2026: FraudControlService::check() + DB::transaction() + correlation_id + AuditService.
 * Никаких фасадов, только constructor injection. DTO на входе для create.
 */
final readonly class BeautyServiceManager
{
    public function __construct(
        private FraudControlService $fraud,
        private AuditService $audit,
        private DatabaseManager $db,
        private LoggerInterface $logger,
        private Guard $guard,
    ) {
    }

    /**
     * Создать новую услугу салона красоты.
     */
    public function createService(CreateServiceDto $dto): BeautyServiceModel
    {
        $correlationId = $dto->getCorrelationId();
        $data = $dto->toArray();

        $this->fraud->check(
            userId: $dto->getUserId(),
            operationType: 'beauty_create_service',
            amount: (int) ($data['price'] ?? 0),
            correlationId: $correlationId,
        );

        return $this->db->transaction(function () use ($data, $correlationId): BeautyServiceModel {
            $service = BeautyServiceModel::create(array_merge($data, [
                'uuid' => Str::uuid()->toString(),
                'correlation_id' => $correlationId,
            ]));

            $this->audit->record(
                action: 'beauty_service_created',
                subjectType: BeautyServiceModel::class,
                subjectId: $service->id,
                oldValues: [],
                newValues: $service->toArray(),
                correlationId: $correlationId,
            );

            $this->logger->info('Beauty service created', [
                'service_id' => $service->id,
                'name' => $service->name,
                'salon_id' => $service->salon_id ?? null,
                'correlation_id' => $correlationId,
            ]);

            return $service;
        });
    }

    /**
     * Обновить существующую услугу.
     */
    public function updateService(
        BeautyServiceModel $service,
        array $data,
        string $correlationId = '',
    ): BeautyServiceModel {
        $correlationId = $correlationId !== ''
            ? $correlationId
            : ($service->correlation_id ?? Str::uuid()->toString());

        $this->fraud->check(
            userId: (int) ($this->guard->id() ?? 0),
            operationType: 'beauty_update_service',
            amount: (int) ($data['price'] ?? $service->price ?? 0),
            correlationId: $correlationId,
        );

        return $this->db->transaction(function () use ($service, $data, $correlationId): BeautyServiceModel {
            $oldValues = $service->toArray();

            $service->update(array_merge($data, [
                'correlation_id' => $correlationId,
            ]));

            $this->audit->record(
                action: 'beauty_service_updated',
                subjectType: BeautyServiceModel::class,
                subjectId: $service->id,
                oldValues: $oldValues,
                newValues: $service->fresh()->toArray(),
                correlationId: $correlationId,
            );

            $this->logger->info('Beauty service updated', [
                'service_id' => $service->id,
                'changed_fields' => array_keys($data),
                'correlation_id' => $correlationId,
            ]);

            return $service;
        });
    }

    /**
     * Получить все услуги конкретного салона (tenant-scoped через global scope модели).
     */
    public function getSalonServices(int $salonId): Collection
    {
        return BeautyServiceModel::where('salon_id', $salonId)
            ->orderBy('name')
            ->get();
    }

    /**
     * Soft-delete услуги.
     */
    public function deleteService(BeautyServiceModel $service, string $correlationId = ''): bool
    {
        $correlationId = $correlationId !== '' ? $correlationId : Str::uuid()->toString();

        $this->fraud->check(
            userId: (int) ($this->guard->id() ?? 0),
            operationType: 'beauty_delete_service',
            amount: 0,
            correlationId: $correlationId,
        );

        return $this->db->transaction(function () use ($service, $correlationId): bool {
            $oldValues = $service->toArray();

            $result = (bool) $service->delete();

            $this->audit->record(
                action: 'beauty_service_deleted',
                subjectType: BeautyServiceModel::class,
                subjectId: $service->id,
                oldValues: $oldValues,
                newValues: [],
                correlationId: $correlationId,
            );

            $this->logger->info('Beauty service deleted', [
                'service_id' => $service->id,
                'service_name' => $service->name,
                'correlation_id' => $correlationId,
            ]);

            return $result;
        });
    }

    /**
     * Получить услугу по ID.
     */
    public function findById(int $serviceId): BeautyServiceModel
    {
        $service = BeautyServiceModel::find($serviceId);

        if ($service === null) {
            throw new \DomainException("Услуга Beauty (id={$serviceId}) не найдена для текущего тенанта.");
        }

        return $service;
    }
}
