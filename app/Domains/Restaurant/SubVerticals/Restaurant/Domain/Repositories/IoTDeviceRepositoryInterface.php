<?php

declare(strict_types=1);

namespace Modules\Restaurant\Domain\Repositories;

use Modules\Restaurant\Domain\Entities\IoTDevice;
use Modules\Restaurant\Domain\Enums\IoTDeviceType;
use Modules\Restaurant\Domain\Enums\IoTProtocol;

interface IoTDeviceRepositoryInterface
{
    public function save(IoTDevice $device): IoTDevice;
    
    public function findById(int $id): ?IoTDevice;
    
    public function findByIdentifier(string $identifier): ?IoTDevice;
    
    public function findByTenant(int $tenantId): array;
    
    public function findByStation(int $kitchenStationId): array;
    
    public function findByType(IoTDeviceType $type): array;
    
    public function findByProtocol(IoTProtocol $protocol): array;
    
    public function findOnline(): array;
    
    public function findOfflineFor(int $minutes): array;
    
    public function delete(int $id): void;
}
