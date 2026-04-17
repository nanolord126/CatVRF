<?php declare(strict_types=1);

namespace App\Observers;

use App\Domains\Medical\Models\Clinic;
use App\Services\Cache\CacheService;
use Illuminate\Support\Facades\Log;

/**
 * Clinic Observer
 *
 * Production 2026 CANON - Automatic Cache Invalidation
 *
 * Automatically invalidates cache when clinics are created, updated, or deleted.
 * This ensures that clinic recommendations and slots are always based on fresh data.
 *
 * @author CatVRF Team
 * @version 2026.04.18
 */
final readonly class ClinicObserver
{
    public function __construct(
        private readonly CacheService $cache,
    ) {}

    /**
     * Handle the Clinic "created" event.
     */
    public function created(Clinic $clinic): void
    {
        $this->invalidateRelatedCache($clinic);
    }

    /**
     * Handle the Clinic "updated" event.
     */
    public function updated(Clinic $clinic): void
    {
        $this->invalidateRelatedCache($clinic);
    }

    /**
     * Handle the Clinic "deleted" event.
     */
    public function deleted(Clinic $clinic): void
    {
        $this->invalidateRelatedCache($clinic);
    }

    /**
     * Invalidate all related cache for the clinic.
     */
    private function invalidateRelatedCache(Clinic $clinic): void
    {
        $tenantId = $clinic->tenant_id;
        $clinicId = $clinic->id;

        try {
            // Invalidate clinic-specific cache
            $this->cache->invalidateClinic($tenantId, $clinicId);

            // Invalidate slots cache for the clinic
            $this->cache->invalidateSlots($tenantId, null, $clinicId);

            // Invalidate recommendations cache for the medical vertical
            $this->cache->invalidateVertical($tenantId, 'medical');

            // Invalidate dynamic price cache for the clinic
            $this->cache->invalidateDynamicPrice($tenantId, 'clinic', $clinicId);

            Log::info('Clinic cache invalidated', [
                'tenant_id' => $tenantId,
                'clinic_id' => $clinicId,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to invalidate clinic cache', [
                'tenant_id' => $tenantId,
                'clinic_id' => $clinicId,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
