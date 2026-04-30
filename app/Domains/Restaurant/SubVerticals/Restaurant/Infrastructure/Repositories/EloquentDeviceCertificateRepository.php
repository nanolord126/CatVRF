<?php

declare(strict_types=1);

namespace Modules\Restaurant\Infrastructure\Repositories;

use Illuminate\Cache\CacheManager;
use Modules\Restaurant\Domain\Entities\DeviceCertificate;
use Modules\Restaurant\Domain\Repositories\DeviceCertificateRepositoryInterface;
use Modules\Restaurant\Infrastructure\Models\DeviceCertificateModel;
use Carbon\CarbonImmutable;
use Exception;

/**
 * EloquentDeviceCertificateRepository
 *
 * CatVRF 2026 Canon - Production Mandatory
 * - Dependency injection instead of facades
 * - Readonly class
 */
final readonly class EloquentDeviceCertificateRepository implements DeviceCertificateRepositoryInterface
{
    private const CACHE_TTL = 300; // 5 minutes

    public function __construct(
        private readonly CacheManager $cache,
    ) {}

    public function findById(int $id): ?DeviceCertificate
    {
        $cacheKey = "device_certificate:{$id}";
        
        return $this->cache->remember($cacheKey, self::CACHE_TTL, function () use ($id) {
            $model = DeviceCertificateModel::find($id);
            return $model?->toDomain();
        });
    }

    public function findByFingerprint(string $fingerprint): ?DeviceCertificate
    {
        $cacheKey = "device_cert_fingerprint:{$fingerprint}";
        
        return $this->cache->remember($cacheKey, self::CACHE_TTL, function () use ($fingerprint) {
            $model = DeviceCertificateModel::where('certificate_fingerprint', $fingerprint)
                ->where('is_revoked', false)
                ->first();
            return $model?->toDomain();
        });
    }

    public function findByDeviceId(int $iotDeviceId): ?DeviceCertificate
    {
        $cacheKey = "device_cert_by_device:{$iotDeviceId}";
        
        return $this->cache->remember($cacheKey, self::CACHE_TTL, function () use ($iotDeviceId) {
            $model = DeviceCertificateModel::where('iot_device_id', $iotDeviceId)
                ->orderBy('created_at', 'desc')
                ->first();
            return $model?->toDomain();
        });
    }

    public function findBySerialNumber(string $serialNumber): ?DeviceCertificate
    {
        $cacheKey = "device_cert_serial:{$serialNumber}";
        
        return $this->cache->remember($cacheKey, self::CACHE_TTL, function () use ($serialNumber) {
            $model = DeviceCertificateModel::where('serial_number', $serialNumber)->first();
            return $model?->toDomain();
        });
    }

    public function findActiveByDeviceId(int $iotDeviceId): ?DeviceCertificate
    {
        $cacheKey = "device_cert_active:{$iotDeviceId}";
        
        return $this->cache->remember($cacheKey, self::CACHE_TTL, function () use ($iotDeviceId) {
            $model = DeviceCertificateModel::where('iot_device_id', $iotDeviceId)
                ->where('is_revoked', false)
                ->where('expires_at', '>', CarbonImmutable::now())
                ->orderBy('created_at', 'desc')
                ->first();
            return $model?->toDomain();
        });
    }

    public function findExpiringWithin(int $days, int $tenantId): array
    {
        $cacheKey = "device_cert_expiring:{$tenantId}:{$days}";
        
        return $this->cache->remember($cacheKey, self::CACHE_TTL, function () use ($days, $tenantId) {
            $models = DeviceCertificateModel::where('tenant_id', $tenantId)
                ->where('is_revoked', false)
                ->where('expires_at', '<=', CarbonImmutable::now()->addDays($days))
                ->where('expires_at', '>', CarbonImmutable::now())
                ->orderBy('expires_at')
                ->get();
            
            return $models->map(fn ($model) => $model->toDomain())->toArray();
        });
    }

    public function findRevokedByTenant(int $tenantId): array
    {
        $cacheKey = "device_cert_revoked:{$tenantId}";
        
        return $this->cache->remember($cacheKey, self::CACHE_TTL, function () use ($tenantId) {
            $models = DeviceCertificateModel::where('tenant_id', $tenantId)
                ->where('is_revoked', true)
                ->orderBy('revoked_at', 'desc')
                ->get();
            
            return $models->map(fn ($model) => $model->toDomain())->toArray();
        });
    }

    public function save(DeviceCertificate $certificate): DeviceCertificate
    {
        $model = DeviceCertificateModel::fromDomain($certificate);
        $model->save();
        
        // Invalidate cache
        $this->cache->forget("device_certificate:{$certificate->id}");
        $this->cache->forget("device_cert_fingerprint:{$certificate->certificateFingerprint}");
        $this->cache->forget("device_cert_by_device:{$certificate->iotDeviceId}");
        $this->cache->forget("device_cert_active:{$certificate->iotDeviceId}");
        $this->cache->forget("device_cert_serial:{$certificate->serialNumber}");
        
        return $model->toDomain();
    }

    public function delete(int $id): void
    {
        $model = DeviceCertificateModel::find($id);
        if ($model === null) {
            throw new Exception("Device certificate not found: {$id}");
        }
        
        $model->delete();
        
        // Invalidate cache
        $this->cache->forget("device_certificate:{$id}");
        $this->cache->forget("device_cert_fingerprint:{$model->certificate_fingerprint}");
        $this->cache->forget("device_cert_by_device:{$model->iot_device_id}");
        $this->cache->forget("device_cert_active:{$model->iot_device_id}");
        $this->cache->forget("device_cert_serial:{$model->serial_number}");
    }
}
