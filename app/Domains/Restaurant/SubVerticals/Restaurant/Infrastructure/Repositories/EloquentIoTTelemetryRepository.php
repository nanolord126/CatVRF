<?php

declare(strict_types=1);

namespace Modules\Restaurant\Infrastructure\Repositories;

use Carbon\CarbonImmutable;
use Modules\Restaurant\Domain\Entities\IoTTelemetry;
use Modules\Restaurant\Domain\Repositories\IoTTelemetryRepositoryInterface;
use Modules\Restaurant\Infrastructure\Models\IoTTelemetryModel;

final class EloquentIoTTelemetryRepository implements IoTTelemetryRepositoryInterface
{
    public function save(IoTTelemetry $telemetry): IoTTelemetry
    {
        $model = IoTTelemetryModel::fromDomain($telemetry);
        $model->save();
        
        return $model->toDomain();
    }
    
    public function findById(int $id): ?IoTTelemetry
    {
        $model = IoTTelemetryModel::find($id);
        return $model?->toDomain();
    }
    
    public function findByDevice(int $deviceId, int $limit = 100): array
    {
        $models = IoTTelemetryModel::byDevice($deviceId)
            ->orderBy('recorded_at', 'desc')
            ->limit($limit)
            ->get();
        return $models->map(fn ($model) => $model->toDomain())->toArray();
    }
    
    public function findByDeviceAndMetric(int $deviceId, string $metricType, int $limit = 100): array
    {
        $models = IoTTelemetryModel::byDevice($deviceId)
            ->byMetric($metricType)
            ->orderBy('recorded_at', 'desc')
            ->limit($limit)
            ->get();
        return $models->map(fn ($model) => $model->toDomain())->toArray();
    }
    
    public function findAlerts(int $deviceId, ?CarbonImmutable $since = null): array
    {
        $query = IoTTelemetryModel::byDevice($deviceId)->alerts();
        
        if ($since !== null) {
            $query->where('recorded_at', '>=', $since);
        }
        
        $models = $query->orderBy('recorded_at', 'desc')->get();
        return $models->map(fn ($model) => $model->toDomain())->toArray();
    }
    
    public function findLatestByDevice(int $deviceId): ?IoTTelemetry
    {
        $model = IoTTelemetryModel::byDevice($deviceId)
            ->orderBy('recorded_at', 'desc')
            ->first();
        return $model?->toDomain();
    }
    
    public function findLatestByDeviceAndMetric(int $deviceId, string $metricType): ?IoTTelemetry
    {
        $model = IoTTelemetryModel::byDevice($deviceId)
            ->byMetric($metricType)
            ->orderBy('recorded_at', 'desc')
            ->first();
        return $model?->toDomain();
    }
    
    public function deleteOlderThan(CarbonImmutable $date): int
    {
        return IoTTelemetryModel::where('recorded_at', '<', $date)->delete();
    }
}
