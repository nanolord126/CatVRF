<?php

declare(strict_types=1);

namespace Modules\Restaurant\Domain\Repositories;

use Modules\Restaurant\Domain\Entities\DeviceCertificate;

interface DeviceCertificateRepositoryInterface
{
    public function findById(int $id): ?DeviceCertificate;

    public function findByFingerprint(string $fingerprint): ?DeviceCertificate;

    public function findByDeviceId(int $iotDeviceId): ?DeviceCertificate;

    public function findBySerialNumber(string $serialNumber): ?DeviceCertificate;

    public function findActiveByDeviceId(int $iotDeviceId): ?DeviceCertificate;

    public function findExpiringWithin(int $days, int $tenantId): array;

    public function findRevokedByTenant(int $tenantId): array;

    public function save(DeviceCertificate $certificate): DeviceCertificate;

    public function delete(int $id): void;
}
