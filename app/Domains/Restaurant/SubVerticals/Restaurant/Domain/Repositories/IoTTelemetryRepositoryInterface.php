<?php

declare(strict_types=1);

namespace Modules\Restaurant\Domain\Repositories;

use Modules\Restaurant\Domain\Entities\IoTTelemetry;
use Carbon\CarbonImmutable;

interface IoTTelemetryRepositoryInterface
{
    public function save(IoTTelemetry $telemetry): IoTTelemetry;
    
    public function findById(int $id): ?IoTTelemetry;
    
    public function findByDevice(int $deviceId, int $limit = 100): array;
    
    public function findByDeviceAndMetric(int $deviceId, string $metricType, int $limit = 100): array;
    
    public function findAlerts(int $deviceId, ?CarbonImmutable $since = null): array;
    
    public function findLatestByDevice(int $deviceId): ?IoTTelemetry;
    
    public function findLatestByDeviceAndMetric(int $deviceId, string $metricType): ?IoTTelemetry;
    
    public function deleteOlderThan(CarbonImmutable $date): int;
}
