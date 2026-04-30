<?php

declare(strict_types=1);

namespace Modules\Restaurant\Infrastructure\Repositories;

use Modules\Restaurant\Domain\Entities\IoTDevice;
use Modules\Restaurant\Domain\Enums\IoTDeviceType;
use Modules\Restaurant\Domain\Enums\IoTProtocol;
use Modules\Restaurant\Domain\Repositories\IoTDeviceRepositoryInterface;
use Modules\Restaurant\Infrastructure\Models\IoTDeviceModel;

final class EloquentIoTDeviceRepository implements IoTDeviceRepositoryInterface
{
    public function save(IoTDevice $device): IoTDevice
    {
        $model = IoTDeviceModel::fromDomain($device);
        $model->save();
        
        return $model->toDomain();
    }
    
    public function findById(int $id): ?IoTDevice
    {
        $model = IoTDeviceModel::find($id);
        return $model?->toDomain();
    }
    
    public function findByIdentifier(string $identifier): ?IoTDevice
    {
        $model = IoTDeviceModel::where('device_identifier', $identifier)->first();
        return $model?->toDomain();
    }
    
    public function findByTenant(int $tenantId): array
    {
        // Note: tenant scope is applied globally, so we don't need to filter here
        $models = IoTDeviceModel::active()->get();
        return $models->map(fn ($model) => $model->toDomain())->toArray();
    }
    
    public function findByStation(int $kitchenStationId): array
    {
        $models = IoTDeviceModel::where('kitchen_station_id', $kitchenStationId)->active()->get();
        return $models->map(fn ($model) => $model->toDomain())->toArray();
    }
    
    public function findByType(IoTDeviceType $type): array
    {
        $models = IoTDeviceModel::byType($type)->active()->get();
        return $models->map(fn ($model) => $model->toDomain())->toArray();
    }
    
    public function findByProtocol(IoTProtocol $protocol): array
    {
        $models = IoTDeviceModel::byProtocol($protocol)->active()->get();
        return $models->map(fn ($model) => $model->toDomain())->toArray();
    }
    
    public function findOnline(): array
    {
        $models = IoTDeviceModel::online()->active()->get();
        return $models->map(fn ($model) => $model->toDomain())->toArray();
    }
    
    public function findOfflineFor(int $minutes): array
    {
        $cutoff = now()->subMinutes($minutes);
        $models = IoTDeviceModel::where('last_seen_at', '<', $cutoff)
            ->orWhereNull('last_seen_at')
            ->active()
            ->get();
        return $models->map(fn ($model) => $model->toDomain())->toArray();
    }
    
    public function delete(int $id): void
    {
        IoTDeviceModel::destroy($id);
    }
}
