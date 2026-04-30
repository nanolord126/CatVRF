<?php

declare(strict_types=1);

namespace Modules\Restaurant\Domain\Repositories;

use Modules\Restaurant\Domain\Entities\IoTSecurityEvent;
use Modules\Restaurant\Domain\Enums\IoTSecurityEventType;
use Carbon\CarbonImmutable;

interface IoTSecurityEventRepositoryInterface
{
    public function findById(int $id): ?IoTSecurityEvent;

    public function findByDeviceId(int $iotDeviceId, int $limit = 100): array;

    public function findByTenant(int $tenantId, int $limit = 100): array;

    public function findByEventType(IoTSecurityEventType $eventType, int $tenantId, int $limit = 100): array;

    public function findCriticalByTenant(int $tenantId, int $limit = 50): array;

    public function findUnresolvedByTenant(int $tenantId, int $limit = 50): array;

    public function findByCorrelationId(string $correlationId): array;

    public function findByDateRange(int $tenantId, CarbonImmutable $start, CarbonImmutable $end, int $limit = 100): array;

    public function save(IoTSecurityEvent $event): IoTSecurityEvent;

    public function delete(int $id): void;

    public function deleteOlderThan(CarbonImmutable $date): int;
}
