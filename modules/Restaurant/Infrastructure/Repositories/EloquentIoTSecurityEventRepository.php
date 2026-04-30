<?php

declare(strict_types=1);

namespace Modules\Restaurant\Infrastructure\Repositories;

use Modules\Restaurant\Domain\Entities\IoTSecurityEvent;
use Modules\Restaurant\Domain\Repositories\IoTSecurityEventRepositoryInterface;
use Modules\Restaurant\Domain\Enums\IoTSecurityEventType;
use Modules\Restaurant\Infrastructure\Models\IoTSecurityEventModel;
use Carbon\CarbonImmutable;
use Exception;

final class EloquentIoTSecurityEventRepository implements IoTSecurityEventRepositoryInterface
{
    public function findById(int $id): ?IoTSecurityEvent
    {
        $model = IoTSecurityEventModel::find($id);
        return $model?->toDomain();
    }

    public function findByDeviceId(int $iotDeviceId, int $limit = 100): array
    {
        $models = IoTSecurityEventModel::where('iot_device_id', $iotDeviceId)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
        
        return $models->map(fn ($model) => $model->toDomain())->toArray();
    }

    public function findByTenant(int $tenantId, int $limit = 100): array
    {
        $models = IoTSecurityEventModel::where('tenant_id', $tenantId)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
        
        return $models->map(fn ($model) => $model->toDomain())->toArray();
    }

    public function findByEventType(IoTSecurityEventType $eventType, int $tenantId, int $limit = 100): array
    {
        $models = IoTSecurityEventModel::where('tenant_id', $tenantId)
            ->where('event_type', $eventType->value)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
        
        return $models->map(fn ($model) => $model->toDomain())->toArray();
    }

    public function findCriticalByTenant(int $tenantId, int $limit = 50): array
    {
        $models = IoTSecurityEventModel::where('tenant_id', $tenantId)
            ->whereIn('severity', ['critical', 'emergency'])
            ->whereNull('resolved_at')
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
        
        return $models->map(fn ($model) => $model->toDomain())->toArray();
    }

    public function findUnresolvedByTenant(int $tenantId, int $limit = 50): array
    {
        $models = IoTSecurityEventModel::where('tenant_id', $tenantId)
            ->whereNull('resolved_at')
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
        
        return $models->map(fn ($model) => $model->toDomain())->toArray();
    }

    public function findByCorrelationId(string $correlationId): array
    {
        $models = IoTSecurityEventModel::where('correlation_id', $correlationId)
            ->orderBy('created_at', 'asc')
            ->get();
        
        return $models->map(fn ($model) => $model->toDomain())->toArray();
    }

    public function findByDateRange(int $tenantId, CarbonImmutable $start, CarbonImmutable $end, int $limit = 100): array
    {
        $models = IoTSecurityEventModel::where('tenant_id', $tenantId)
            ->whereBetween('created_at', [$start, $end])
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
        
        return $models->map(fn ($model) => $model->toDomain())->toArray();
    }

    public function save(IoTSecurityEvent $event): IoTSecurityEvent
    {
        $model = IoTSecurityEventModel::fromDomain($event);
        $model->save();
        
        return $model->toDomain();
    }

    public function delete(int $id): void
    {
        $model = IoTSecurityEventModel::find($id);
        if ($model === null) {
            throw new Exception("IoT security event not found: {$id}");
        }
        
        $model->delete();
    }

    public function deleteOlderThan(CarbonImmutable $date): int
    {
        return IoTSecurityEventModel::where('created_at', '<', $date)
            ->whereNotNull('resolved_at')
            ->delete();
    }
}
