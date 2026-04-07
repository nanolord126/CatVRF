<?php

declare(strict_types=1);

namespace App\Domains\RealEstate\Infrastructure\Eloquent\Repositories;

use App\Domains\RealEstate\Domain\Entities\ViewingAppointment;
use App\Domains\RealEstate\Domain\Enums\ViewingStatusEnum;
use App\Domains\RealEstate\Domain\Repository\ViewingRepositoryInterface;
use App\Domains\RealEstate\Domain\ValueObjects\AgentId;
use App\Domains\RealEstate\Domain\ValueObjects\PropertyId;
use App\Domains\RealEstate\Domain\ValueObjects\ViewingId;
use App\Domains\RealEstate\Infrastructure\Eloquent\Models\ViewingAppointmentModel;
use Illuminate\Support\Collection;
use Psr\Log\LoggerInterface;

final class EloquentViewingRepository implements ViewingRepositoryInterface
{
    public function __construct(
        private readonly LoggerInterface $logger) {}
    public function findById(ViewingId $id): ?ViewingAppointment
    {
        $model = ViewingAppointmentModel::withoutGlobalScope('tenant')
            ->find($id->getValue());

        return $model !== null ? $this->toDomain($model) : null;
    }

    public function findByIdAndTenant(ViewingId $id, int $tenantId): ?ViewingAppointment
    {
        $model = ViewingAppointmentModel::withoutGlobalScope('tenant')
            ->where('id', $id->getValue())
            ->where('tenant_id', $tenantId)
            ->first();

        return $model !== null ? $this->toDomain($model) : null;
    }

    public function findByPropertyId(PropertyId $propertyId): Collection
    {
        return ViewingAppointmentModel::withoutGlobalScope('tenant')
            ->where('property_id', $propertyId->getValue())
            ->get()
            ->map(fn (ViewingAppointmentModel $m) => $this->toDomain($m));
    }

    public function findByTenantAndStatus(int $tenantId, ViewingStatusEnum $status): Collection
    {
        return ViewingAppointmentModel::withoutGlobalScope('tenant')
            ->where('tenant_id', $tenantId)
            ->where('status', $status->value)
            ->orderBy('scheduled_at')
            ->get()
            ->map(fn (ViewingAppointmentModel $m) => $this->toDomain($m));
    }

    public function findByTenantId(int $tenantId): Collection
    {
        return ViewingAppointmentModel::withoutGlobalScope('tenant')
            ->where('tenant_id', $tenantId)
            ->orderByDesc('scheduled_at')
            ->get()
            ->map(fn (ViewingAppointmentModel $m) => $this->toDomain($m));
    }

    public function hasConflict(
        PropertyId $propertyId,
        \DateTimeImmutable $scheduledAt,
        ?ViewingId $excludeId = null,
    ): bool {
        // Окно конфликта — 90 минут вокруг запрошенного времени
        $from = $scheduledAt->modify('-90 minutes')->format('Y-m-d H:i:s');
        $to   = $scheduledAt->modify('+90 minutes')->format('Y-m-d H:i:s');

        $builder = ViewingAppointmentModel::withoutGlobalScope('tenant')
            ->where('property_id', $propertyId->getValue())
            ->whereIn('status', [ViewingStatusEnum::Pending->value, ViewingStatusEnum::Confirmed->value])
            ->whereBetween('scheduled_at', [$from, $to]);

        if ($excludeId !== null) {
            $builder->where('id', '!=', $excludeId->getValue());
        }

        return $builder->exists();
    }

    public function save(ViewingAppointment $viewing): void
    {
        $data = [
            'id'                  => $viewing->getId()->getValue(),
            'tenant_id'           => $viewing->getTenantId(),
            'property_id'         => $viewing->getPropertyId()->getValue(),
            'client_id'           => $viewing->getClientId(),
            'agent_id'            => $viewing->getAgentId()->getValue(),
            'scheduled_at'        => $viewing->getScheduledAt()->format('Y-m-d H:i:s'),
            'status'              => $viewing->getStatus()->value,
            'client_name'         => $viewing->getClientName(),
            'client_phone'        => $viewing->getClientPhone(),
            'notes'               => $viewing->getNotes(),
            'cancellation_reason' => $viewing->getCancellationReason(),
            'correlation_id'      => $viewing->getCorrelationId(),
        ];

        ViewingAppointmentModel::withoutGlobalScope('tenant')
            ->updateOrCreate(['id' => $data['id']], $data);

        $this->logger->info('ViewingRepository::save', [
            'viewing_id'     => $data['id'],
            'status'         => $data['status'],
            'correlation_id' => $data['correlation_id'],
        ]);
    }

    private function toDomain(ViewingAppointmentModel $model): ViewingAppointment
    {
        return new ViewingAppointment(
            id:                  ViewingId::fromString($model->id),
            tenantId:            $model->tenant_id,
            propertyId:          PropertyId::fromString($model->property_id),
            clientId:            $model->client_id,
            agentId:             AgentId::fromString($model->agent_id),
            scheduledAt:         new \DateTimeImmutable($model->scheduled_at->format('Y-m-d H:i:s')),
            status:              ViewingStatusEnum::from($model->status),
            clientName:          $model->client_name ?? '',
            clientPhone:         $model->client_phone ?? '',
            notes:               $model->notes,
            cancellationReason:  $model->cancellation_reason,
            correlationId:       $model->correlation_id ?? '',
        );
    }
}
