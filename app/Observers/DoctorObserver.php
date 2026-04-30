<?php

declare(strict_types=1);

namespace App\Observers;

use Psr\Log\LoggerInterface;

use App\Domains\Medical\Models\Doctor;
use App\Services\Cache\CacheService;
use Illuminate\Log\LogManager;

/**
 * Doctor Observer
 *
 * Production 2026 CANON - Automatic Cache Invalidation
 *
 * Automatically invalidates cache when doctors are created, updated, or deleted.
 * This ensures that doctor recommendations and slots are always based on fresh data.
 *
 * @author CatVRF Team
 *
 * @version 2026.04.18
 */
final readonly class DoctorObserver
{
    public function __construct(private readonly LoggerInterface $logger,
        private readonly CacheService $cache,
        private readonly LogManager $log,) {}

    /**
     * Handle the Doctor "created" event.
     */
    public function created(Doctor $doctor): void
    {
        $this->invalidateRelatedCache($doctor);
    }

    /**
     * Handle the Doctor "updated" event.
     */
    public function updated(Doctor $doctor): void
    {
        $this->invalidateRelatedCache($doctor);
    }

    /**
     * Handle the Doctor "deleted" event.
     */
    public function deleted(Doctor $doctor): void
    {
        $this->invalidateRelatedCache($doctor);
    }

    /**
     * Invalidate all related cache for the doctor.
     */
    private function invalidateRelatedCache(Doctor $doctor): void
    {
        $tenantId = $doctor->tenant_id;
        $doctorId = $doctor->id;
        $clinicId = $doctor->clinic_id;

        try {
            // Invalidate doctor-specific cache
            $this->cache->invalidateDoctor($tenantId, $doctorId);

            // Invalidate slots cache for the doctor
            $this->cache->invalidateSlots($tenantId, $doctorId);

            // Invalidate clinic cache (if clinic is associated)
            if ($clinicId) {
                $this->cache->invalidateClinic($tenantId, $clinicId);
            }

            // Invalidate recommendations cache for the medical vertical
            $this->cache->invalidateVertical($tenantId, 'medical');

            $this->log->$this->logger->info('Doctor cache invalidated', [
                'tenant_id' => $tenantId,
                'doctor_id' => $doctorId,
                'clinic_id' => $clinicId,
            ]);
        } catch (\Exception $e) {
            $this->log->error('Failed to invalidate doctor cache', [
                'tenant_id' => $tenantId,
                'doctor_id' => $doctorId,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
